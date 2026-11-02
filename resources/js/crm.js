/* Original CRM interactions; no demo-only fake saves or disconnected controls. */
const ready = () => {
    const doc = document.documentElement;
    const safeStore = (key, value) => { try { localStorage.setItem(key, value); } catch (_) {} };
    const safeRead = (key) => { try { return localStorage.getItem(key); } catch (_) { return null; } };
    if (safeRead('fida-crm-theme') === 'dark') doc.dataset.mode = 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
        doc.dataset.mode = doc.dataset.mode === 'dark' ? 'light' : 'dark';
        safeStore('fida-crm-theme', doc.dataset.mode);
    }));

    const nav = document.querySelector('[data-nav-scroll]');
    const activeNav = nav?.querySelector('.hub-side-link.is-current, .nav-item.active');
    if (nav && activeNav) requestAnimationFrame(() => {
        const el = activeNav.getBoundingClientRect();
        const parent = nav.getBoundingClientRect();
        if (el.top < parent.top + 35 || el.bottom > parent.bottom - 35)
            nav.scrollTop += (el.top - parent.top) - (parent.height / 2) + (el.height / 2);
    });

    const sidebar = document.querySelector('#crmSidebar');
    const backdrop = document.querySelector('#hubBackdrop');
    const setSidebar = open => {
        sidebar?.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        document.querySelector('#sidebarToggle')?.setAttribute('aria-expanded', String(open));
    };
    document.querySelector('#sidebarToggle')?.addEventListener('click', () => setSidebar(!sidebar?.classList.contains('is-open')));
    document.querySelector('#sidebarClose')?.addEventListener('click', () => setSidebar(false));
    backdrop?.addEventListener('click', () => setSidebar(false));

    const search = document.querySelector('.hub-global-search input');
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') setSidebar(false);
        const typing = e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement || e.target?.isContentEditable;
        if (((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') || (e.key === '/' && !typing)) {
            e.preventDefault(); search?.focus(); search?.select();
        }
    });

    const alert = window.FidaAlert;
    const toast = (icon, title) => {
        if (alert) alert.fire({ toast:true, position:'top-end', showConfirmButton:false, timer:3500, timerProgressBar:true, icon, title });
        else if (icon === 'error') console.error(title);
    };
    const serverMessage = document.querySelector('.hub-server-message');
    if (serverMessage) toast('success', serverMessage.textContent.trim());

    if (window.$?.fn?.select2) {
        window.$('.hub-enhanced-select').each(function () {
            const element = window.$(this);
            const parent = element.closest('.hub-record-center, .hub-form-panel, .hub-feature-form, .crm-card');
            const opts = { width:'100%', placeholder: element.data('placeholder') || 'Select an option', allowClear:!element.prop('required') };
            if (parent.length) opts.dropdownParent = parent;
            element.select2(opts);
        });
    }

    document.querySelectorAll('form[data-smart-form], form[data-confirm]').forEach(form => {
        form.addEventListener('submit', async event => {
            if (!form.checkValidity()) return;
            if (form.dataset.confirm && form.dataset.confirmed !== 'yes') {
                event.preventDefault();
                const proceed = alert ? await alert.fire({icon:'warning',title:'Confirm action',text:form.dataset.confirm,showCancelButton:true,confirmButtonText:'Confirm',confirmButtonColor:'#ae651b',reverseButtons:true}) : {isConfirmed:window.confirm(form.dataset.confirm)};
                if (proceed.isConfirmed) {
                    form.dataset.confirmed = 'yes';
                    form.requestSubmit(event.submitter || form.querySelector('[type="submit"]'));
                }
                return;
            }
            form.classList.add('is-submitting');
            const button = event.submitter || form.querySelector('button[type="submit"]');
            if (button) {
                button.classList.add('is-busy');
                button.setAttribute('aria-busy', 'true');
                const label = button.querySelector('[data-submit-label]');
                if (label) label.textContent = 'Saving…';
            }
        });
    });

    document.querySelectorAll('form[data-auto-import]').forEach(form => {
        form.querySelector('input[type="file"]')?.addEventListener('change', async event => {
            const input = event.target;
            if (!input.files?.length) return;
            const confirmed = alert ? (await alert.fire({title:'Import records?',text:`Import ${input.files[0].name}? The CSV must match the record field names.`,icon:'question',showCancelButton:true,confirmButtonText:'Import',confirmButtonColor:'#ae651b'})).isConfirmed : window.confirm(`Import ${input.files[0].name}?`);
            if (confirmed) {
                const progress = document.querySelector('[data-navigation-progress]');
                progress?.classList.add('is-visible');
                form.submit();
            } else input.value = '';
        });
    });

    // A lightweight navigation progress cue for full Laravel page loads.
    document.addEventListener('click', event => {
        const link = event.target.closest?.('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.href;
        if (!href || !href.startsWith(location.origin) || link.hash && link.pathname === location.pathname) return;
        document.querySelector('[data-navigation-progress]')?.classList.add('is-visible');
    });

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let draggedDeal = null;
    document.querySelectorAll('.crm-deal-card').forEach(card => {
        card.addEventListener('dragstart', event => { draggedDeal = card.dataset.deal; card.classList.add('dragging'); event.dataTransfer.effectAllowed = 'move'; });
        card.addEventListener('dragend', () => { draggedDeal = null; card.classList.remove('dragging'); });
    });
    document.querySelectorAll('[data-dropzone]').forEach(zone => {
        zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('drag-hover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-hover'));
        zone.addEventListener('drop', async event => {
            event.preventDefault(); zone.classList.remove('drag-hover');
            if (!draggedDeal) return;
            const card = [...document.querySelectorAll('.crm-deal-card')].find(x => x.dataset.deal === draggedDeal);
            if (!card) return;
            const original = card.parentElement;
            zone.appendChild(card);
            try {
                const result = await fetch(`/pipeline/${encodeURIComponent(draggedDeal)}/move`, {method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},body:JSON.stringify({stage_id:zone.dataset.stage})});
                if (!result.ok) throw Error('The deal stage was not saved.');
                toast('success','Deal moved successfully');
                location.reload();
            } catch (error) { original.appendChild(card); toast('error',error.message); }
        });
    });

    const lines = document.querySelector('#salesLineItems');
    const template = document.querySelector('#salesLineTemplate');
    if (lines && template) {
        const recalculate = () => {
            let grand = 0;
            lines.querySelectorAll('tr[data-line]').forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach(el => el.name = `lines[${index}][${el.dataset.name}]`);
                const number = field => Number(row.querySelector(`[name$="[${field}]"]`)?.value || 0);
                const gross = number('quantity') * number('unit_price');
                const discounted = gross * (1 - number('discount_percent') / 100);
                const amount = discounted * (1 + number('tax_percent') / 100);
                grand += amount;
                const target = row.querySelector('[data-total]');
                if (target) target.textContent = Number.isFinite(amount) ? amount.toFixed(2) : '—';
            });
            const target = document.querySelector('[data-grand-total]');
            if (target) target.textContent = Number.isFinite(grand) ? grand.toFixed(2) : '—';
        };
        const enhanceNewSelect = row => {
            if (window.$?.fn?.select2) window.$(row).find('[data-product-picker]').select2({width:'100%',dropdownParent:window.$(document.body)});
        };
        document.querySelector('[data-add-line]')?.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            lines.appendChild(fragment);
            enhanceNewSelect(lines.lastElementChild);
            recalculate();
        });
        lines.addEventListener('click', event => {
            if (!event.target.closest('[data-remove-line]')) return;
            if (lines.querySelectorAll('tr[data-line]').length <= 1) return;
            const row = event.target.closest('tr');
            if (window.$?.fn?.select2) window.$(row).find('.select2-hidden-accessible').select2('destroy');
            row.remove(); recalculate();
        });
        lines.addEventListener('change', event => {
            if (!event.target.matches('[data-product-picker]')) { recalculate(); return; }
            const selected = event.target.selectedOptions[0];
            if (!selected?.dataset.price) { recalculate(); return; }
            const currency = document.querySelector('[name="currency"]')?.value;
            if (selected.dataset.currency && currency !== selected.dataset.currency) {
                toast('error',`The product uses ${selected.dataset.currency}; choose a matching document currency.`);
                event.target.value = ''; recalculate(); return;
            }
            const row = event.target.closest('tr');
            const set = (name, value) => { const input = row.querySelector(`[name$="[${name}]"], [data-name="${name}"]`); if (input) input.value = value; };
            set('description', selected.dataset.label); set('unit_price',selected.dataset.price); set('tax_percent',selected.dataset.tax || 0);
            recalculate();
        });
        lines.addEventListener('input',recalculate);
        if (window.$?.fn?.select2) window.$(lines).find('[data-product-picker]').select2({width:'100%',dropdownParent:window.$(document.body)});
        recalculate();
    }
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',ready); else ready();
