#!/usr/bin/env python3
"""Repair a historic bulk-edit of form elements; parse tag closers respecting quotes."""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]/'resources/views'
for f in root.rglob('*.blade.php'):
    source=f.read_text()
    source=source.replace('- data-smart-form>', '->')
    out=[];cursor=0
    for match in re.finditer(r'<form\b',source,flags=re.I):
        if match.start()<cursor:continue
        out.append(source[cursor:match.start()])
        i=match.start();quote=None
        while i<len(source):
            c=source[i]
            if quote:
                if c==quote:quote=None
            elif c in "'\"":quote=c
            elif c=='>':break
            i+=1
        if i==len(source):raise ValueError(f'unclosed form tag {f}')
        tag=source[match.start():i+1]
        if re.search(r'\bmethod\s*=\s*[\'\"]POST[\'\"]',tag,re.I) and 'data-smart-form' not in tag:
            tag=tag[:-1]+' data-smart-form>'
        out.append(tag);cursor=i+1
    out.append(source[cursor:])
    f.write_text(''.join(out))
print('Repaired & normalized form tag attributes')
