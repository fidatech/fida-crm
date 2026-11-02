#!/usr/bin/env python3
"""Offline Blade structural and embedded-PHP syntax checks (not a replacement for php artisan view:cache)."""
import pathlib, re, subprocess, sys, tempfile
ROOT=pathlib.Path(__file__).resolve().parents[1]
views=sorted((ROOT/'resources/views').rglob('*.blade.php'))
OPEN={'if':'endif','unless':'endunless','foreach':'endforeach','forelse':'endforelse','for':'endfor','while':'endwhile','switch':'endswitch','error':'enderror','auth':'endauth','guest':'endguest','push':'endpush'}
STARTERS={'section','php'}
MID={'else','elseif','empty','case','default'}
TOKENS=re.compile(r'(?<!@)@([a-zA-Z_][\w]*)\b')
errors=[]
compiled=[]

def parentheses(text,start):
    if start>=len(text) or text[start]!='(': return None,start
    n=1; i=start+1; quote=None;escape=False
    while i<len(text):
        c=text[i]
        if quote:
            if escape:escape=False
            elif c=='\\':escape=True
            elif c==quote:quote=None
        else:
            if c in "'\"":quote=c
            elif c=='(':n+=1
            elif c==')':
                n-=1
                if not n:return text[start+1:i],i+1
        i+=1
    raise ValueError('unclosed parentheses')

for f in views:
    s=f.read_text(); stack=[]; exprs=[]; pos=0
    for m in TOKENS.finditer(s):
        name=m.group(1);loc=s.count('\n',0,m.start())+1
        if name=='php':
            if s[m.end():].lstrip().startswith('('): errors.append(f'{f.relative_to(ROOT)}:{loc} unsupported inline @php()'); continue
            stack.append((name,loc));continue
        if name=='endphp':
            if not stack or stack[-1][0]!='php': errors.append(f'{f.relative_to(ROOT)}:{loc} unexpected @endphp')
            else:stack.pop()
            continue
        if name in OPEN:stack.append((name,loc))
        elif name=='section':
            # @section('title','value') is inline; @section('content') opens a section.
            arg,_=parentheses(s,m.end())
            if arg is not None and ',' not in arg:stack.append(('section',loc))
        elif name.startswith('end') and name not in ('endphp',):
            expected=name
            if expected=='endsection' and stack and stack[-1][0]=='section':stack.pop()
            elif stack and OPEN.get(stack[-1][0])==expected:stack.pop()
            elif expected in ('endforeach','endif','endforelse','endfor','endwhile','enderror','endswitch','endunless','endpush','endauth','endguest','endsection'):
                errors.append(f'{f.relative_to(ROOT)}:{loc} unexpected @{name}; open stack={stack[-4:]}')
        elif name=='elseif' and (not stack or stack[-1][0]!='if'):
            errors.append(f'{f.relative_to(ROOT)}:{loc} @elseif outside @if')
        elif name=='else' and (not stack or stack[-1][0] not in ('if','unless')):
            errors.append(f'{f.relative_to(ROOT)}:{loc} @else outside conditional')
        elif name=='empty' and (not stack or stack[-1][0]!='forelse'):
            errors.append(f'{f.relative_to(ROOT)}:{loc} @empty outside @forelse')
        if name in ('if','elseif','unless','foreach','forelse','for','while','switch','selected','checked','required','disabled','readonly','class'):
            start=m.end()
            if start<len(s) and s[start]=='(':
                try:
                    expr,_=parentheses(s,start)
                    if name in ('foreach','forelse'):exprs.append(f'foreach({expr}) {{ }}')
                    elif name=='for':exprs.append(f'for({expr}) {{ }}')
                    elif name=='while':exprs.append(f'while({expr}) {{ break; }}')
                    elif name=='switch':exprs.append(f'switch({expr}) {{ }}')
                    else:exprs.append(f'if({expr}) {{ }}')
                except Exception as e:errors.append(f'{f.relative_to(ROOT)}:{loc} @{name} parentheses: {e}')
    for name,loc in stack:errors.append(f'{f.relative_to(ROOT)}:{loc} unclosed @{name}')
    for php in re.findall(r'@php\s*\n(.*?)@endphp',s,re.S):exprs.append(php)
    for expr in re.findall(r'\{\{\s*(.*?)\s*\}\}',s,re.S):
        if re.search(r'data-smart-form|data-confirm|class=|href=',expr):
            errors.append(f'{f.relative_to(ROOT)}: HTML attribute unexpectedly inside Blade expression')
        if expr.strip():exprs.append(f'echo ({expr.strip()});')
    compiled.append((f,exprs))

with tempfile.TemporaryDirectory() as td:
    for f,exprs in compiled:
        p=pathlib.Path(td)/(f.stem+'.'+str(views.index(f))+'.php')
        p.write_text('<?php\n'+'\n'.join(exprs)+'\n')
        result=subprocess.run(['php','-l',str(p)],capture_output=True,text=True)
        if result.returncode:errors.append(str(f.relative_to(ROOT))+': '+result.stderr.strip().replace(str(p),'embedded-PHP'))

if errors:
    for err in errors:print('FAIL',err,file=sys.stderr)
    sys.exit(1)
print(f'PASS: {len(views)} Blade files checked for matching directives and embedded PHP syntax')
print('NOTE: Requires php artisan view:cache for authoritative Blade compilation.')
