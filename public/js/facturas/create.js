/* eslint-disable no-console */
(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
  const money = (n) => new Intl.NumberFormat('es-MX',{style:'currency', currency:'MXN'}).format(Number(n||0));

  const root = document.getElementById('facturaRoot');
  if (!root) return;

  // ---- Config / endpoints ----
  const rfcId = Number(root.getAttribute('data-rfc-id')||0);
  const urls = {
    nextFolio: root.getAttribute('data-next-folio-url'),
    prodBuscar: root.getAttribute('data-prod-buscar-url'),
    claveProdServ: root.getAttribute('data-clave-prod-serv-url'),
    claveUnidad: root.getAttribute('data-clave-unidad-url'),
    clientesBase: JSON.parse(document.getElementById('rutas-config')?.textContent||'{}').clientesBase || '/catalogos/clientes',
  };

  const csrf = root.getAttribute('data-csrf');
  const clientes = JSON.parse(root.getAttribute('data-clientes')||'[]');
  const impuestosCfg = JSON.parse(document.getElementById('impuestos-config')?.textContent || '{}');

  // ---- Estado ----
  const state = {
    tipoComprobante: 'I',
    serie: '', folio: '',
    fecha: $('#fecha')?.value || '',
    metodoPago: 'PUE',
    formaPago: '03',
    comentariosPdf: '',
    cliente_id: '',
    conceptos: [], // {producto_id, descripcion, cantidad, precio_unitario, descuento, importe, claveProdServ, claveUnidad, unidad, impuestos:[]}
    impuestosGlobales: [], // {tipo, impuesto, tasa}
  };

  // ---- Util ----
  const calcImporte = (c) => {
    const base = Number(c.cantidad||0) * Number(c.precio_unitario||0) - Number(c.descuento||0);
    return Math.max(0, base);
  };

  const recalcTotales = () => {
    let subtotal = 0;
    let traslados = 0;
    let retenciones = 0;

    state.conceptos.forEach(c => {
      c.importe = calcImporte(c);
      subtotal += c.importe;
      (c.impuestos||[]).forEach(imp => {
        const tasa = Number(imp.tasa)/100; // porcentaje
        const monto = c.importe * tasa;
        if (imp.tipo === 'trasladado') traslados += monto; else retenciones += monto;
      });
    });

    // Globales sobre subtotal
    (state.impuestosGlobales||[]).forEach(imp => {
      const tasa = Number(imp.tasa)/100;
      const monto = subtotal * tasa;
      if (imp.tipo === 'trasladado') traslados += monto; else retenciones += monto;
    });

    const total = subtotal + traslados - retenciones;
    $('#subtotalGeneral').textContent = money(subtotal);
    $('#totalImpuestosTrasladados').textContent = money(traslados);
    $('#totalImpuestosRetenidos').textContent = money(retenciones);
    $('#totalGeneral').textContent = money(total);
  };

  // ---- Serie / folio ----
  const pedirSiguienteFolio = async () => {
    try {
      const tipo = state.tipoComprobante;
      const url = `${urls.nextFolio}?tipo=${encodeURIComponent(tipo)}&rfc=${encodeURIComponent(rfcId)}`;
      const r = await fetch(url);
      const j = await r.json();
      state.serie = j.serie || '';
      state.folio = j.folio || '';
      $('#serie').value = state.serie;
      $('#folio').value = state.folio;
    } catch (e) { console.warn('next-folio failed', e); }
  };

  // ---- Cliente ----
  const renderClienteResumen = () => {
    const cont = $('#clienteResumen');
    cont.innerHTML = '';
    const c = clientes.find(x => String(x.id) === String(state.cliente_id));
    if (!c) return;
    const rows = [
      ['Razón social', c.razon_social||'-'],
      ['RFC', c.rfc||'-'],
      ['Correo', c.email||'-'],
      ['Calle', c.calle||'-'],
      ['No. ext', c.no_ext||'-'],
      ['No. int', c.no_int||'-'],
      ['Colonia', c.colonia||'-'],
      ['Localidad', c.localidad||'-'],
      ['Estado', c.estado||'-'],
      ['País', c.pais||'-'],
      ['C.P.', c.codigo_postal||'-'],
    ];
    rows.forEach(([k,v])=>{
      const d = document.createElement('div');
      d.innerHTML = `<span class="text-gray-400">${k}:</span> <span class="font-medium">${v}</span>`;
      cont.appendChild(d);
    });
  };

  // ---- Conceptos ----
  const conceptosBody = $('#conceptosBody');
  const nuevaFilaConcepto = (c, i) => {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-100 dark:border-gray-700/40';
    tr.innerHTML = `
      <td class="px-2 py-2 align-top">
        <input name="conceptos[${i}][producto_id]" type="hidden" value="${c.producto_id||''}">
        <textarea name="conceptos[${i}][descripcion]" class="form-textarea w-full" rows="2">${c.descripcion||''}</textarea>
      </td>
      <td class="px-2 py-2 align-top w-28">
        <input name="conceptos[${i}][cantidad]" type="number" step="0.001" class="form-input w-full text-right" value="${c.cantidad||1}" data-idx="${i}" data-field="cantidad">
      </td>
      <td class="px-2 py-2 align-top w-32">
        <input name="conceptos[${i}][precio_unitario]" type="number" step="0.01" class="form-input w-full text-right" value="${c.precio_unitario||0}" data-idx="${i}" data-field="precio_unitario">
      </td>
      <td class="px-2 py-2 align-top w-32 text-right">
        <input name="conceptos[${i}][importe]" type="text" class="form-input w-full text-right" value="${money(c.importe||0)}" readonly>
      </td>
      <td class="px-2 py-2 align-top text-sm">
        <input name="conceptos[${i}][claveProdServ]" type="hidden" value="${c.claveProdServ||''}">
        <input name="conceptos[${i}][claveUnidad]" type="hidden" value="${c.claveUnidad||''}">
        <div>
          <span class="text-gray-500">ProdServ:</span>
          <button type="button" class="text-violet-600 underline" data-action="editar-clave-prod-serv" data-idx="${i}">${c.claveProdServ||'—'}</button>
        </div>
        <div>
          <span class="text-gray-500">Unidad:</span>
          <button type="button" class="text-violet-600 underline" data-action="editar-clave-unidad" data-idx="${i}">${c.claveUnidad||'—'} ${c.unidad?('· '+c.unidad):''}</button>
        </div>
      </td>
      <td class="px-2 py-2 align-top">
        <div class="flex flex-wrap gap-1" data-imp-chips="${i}"></div>
        <button type="button" class="btn-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-2 concepto-impuesto-add" data-index="${i}">Agregar impuesto</button>
      </td>
      <td class="px-2 py-2 align-top w-10 text-right">
        <button type="button" class="text-red-500 hover:text-red-600" data-action="quitar-concepto" data-idx="${i}">&times;</button>
      </td>`;
    return tr;
  };

  const renderConceptos = () => {
    conceptosBody.innerHTML = '';
    state.conceptos.forEach((c, i) => {
      c.importe = calcImporte(c);
      const tr = nuevaFilaConcepto(c, i);
      conceptosBody.appendChild(tr);
      renderChipsImpuestos(i);
    });
    recalcTotales();
  };

  const renderChipsImpuestos = (idx) => {
    const cont = $(`[data-imp-chips="${idx}"]`);
    if (!cont) return;
    cont.innerHTML = '';
    (state.conceptos[idx].impuestos||[]).forEach((imp, i) => {
      const chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded bg-violet-500/10 text-violet-700';
      chip.innerHTML = `${imp.tipo==='trasladado'?'Tras':'Ret'} · ${imp.impuesto} ${imp.tasa}% <button type="button" class="ml-1 text-red-500" data-action="del-imp" data-idx="${idx}" data-i="${i}">×</button>`;
      cont.appendChild(chip);
    });
  };

  const agregarConcepto = (c={}) => {
    const base = {
      producto_id: c.id||'', descripcion: c.descripcion||'', cantidad: 1,
      precio_unitario: c.precio||0, descuento: 0,
      claveProdServ: c.clave_prod_serv_id||'', claveUnidad: c.clave_unidad_id||'', unidad: c.unidad||'',
      impuestos: [],
    };
    state.conceptos.push(base);
    renderConceptos();
  };

  // ---- Productos buscar ----
  let buscarDebounce = null;
  const productosBody = $('#productosBody');
  const renderProductos = (list) => {
    productosBody.innerHTML = '';
    list.forEach(p => {
      const tr = document.createElement('tr');
      tr.className = 'border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer';
      tr.innerHTML = `
        <td class="px-2 py-2">${p.descripcion}</td>
        <td class="px-2 py-2">${p.unidad||''}</td>
        <td class="px-2 py-2 text-right">${money(p.precio)}</td>`;
      tr.addEventListener('click', () => agregarConcepto(p));
      productosBody.appendChild(tr);
    });
  };

  const buscarProductos = async (q) => {
    if (!q || q.length < 2) { renderProductos([]); return; }
    try {
      const url = `${urls.prodBuscar}?q=${encodeURIComponent(q)}&rfc=${encodeURIComponent(rfcId)}`;
      const r = await fetch(url);
      const j = await r.json();
      renderProductos(j||[]);
    } catch(e){ console.warn('buscarProductos failed', e); }
  };

  // ---- Impuestos modales ----
  const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.remove('hidden'); };
  const closeModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('hidden'); };
  const fillImpuestoSelects = (tipo, selImp, selTasa) => {
    const imps = impuestosCfg?.[tipo] || {};
    selImp.innerHTML = '';
    Object.keys(imps).forEach(k => {
      const opt = document.createElement('option'); opt.value = k; opt.textContent = k; selImp.appendChild(opt);
    });
    const first = Object.keys(imps)[0];
    const tasas = first ? imps[first] : [];
    selTasa.innerHTML = '';
    tasas.forEach(t => { const o = document.createElement('option'); o.value = t; o.textContent = `${t}%`; selTasa.appendChild(o); });
  };

  // Contexto del modal concepto
  let idxConceptoForImp = null;
  const selTipo = $('#impTipo');
  const selImp = $('#impImpuesto');
  const selTasa = $('#impTasa');
  if (selTipo && selImp && selTasa) {
    fillImpuestoSelects('trasladado', selImp, selTasa);
    selTipo.addEventListener('change', () => fillImpuestoSelects(selTipo.value, selImp, selTasa));
  }

  $('#impConceptoConfirm')?.addEventListener('click', () => {
    if (idxConceptoForImp == null) return;
    const imp = { tipo: selTipo.value, impuesto: selImp.value, tasa: selTasa.value, factor: 'Tasa' };
    state.conceptos[idxConceptoForImp].impuestos = state.conceptos[idxConceptoForImp].impuestos || [];
    state.conceptos[idxConceptoForImp].impuestos.push(imp);
    renderChipsImpuestos(idxConceptoForImp);
    closeModal('modalImpuestoConcepto');
    recalcTotales();
  });
  $('#impConceptoCancel')?.addEventListener('click', () => closeModal('modalImpuestoConcepto'));

  // Modal global
  const selTipoG = $('#impTipoG');
  const selImpG = $('#impImpuestoG');
  const selTasaG = $('#impTasaG');
  if (selTipoG && selImpG && selTasaG) {
    fillImpuestoSelects('trasladado', selImpG, selTasaG);
    selTipoG.addEventListener('change', () => fillImpuestoSelects(selTipoG.value, selImpG, selTasaG));
  }
  $('#impGlobalConfirm')?.addEventListener('click', () => {
    state.impuestosGlobales.push({ tipo: selTipoG.value, impuesto: selImpG.value, tasa: selTasaG.value, factor: 'Tasa' });
    renderImpuestosGlobales();
    closeModal('modalImpuestoGlobal');
    recalcTotales();
  });
  $('#impGlobalCancel')?.addEventListener('click', () => closeModal('modalImpuestoGlobal'));

  const renderImpuestosGlobales = () => {
    const cont = $('#impuestosGlobales');
    cont.innerHTML = '';
    state.impuestosGlobales.forEach((g, i) => {
      const row = document.createElement('div');
      row.className = 'flex items-center justify-between rounded border border-gray-200 dark:border-gray-700/60 px-2 py-1';
      row.innerHTML = `<div class="text-sm">${g.tipo==='trasladado'?'Trasladado':'Retención'} · ${g.impuesto} ${g.tasa}%</div>
                       <button type="button" class="text-red-500" data-action="del-imp-global" data-i="${i}">Eliminar</button>`;
      cont.appendChild(row);
    });
  };

  // ---- Eventos globales ----
  $('#tipoComprobante')?.addEventListener('change', (e)=>{ state.tipoComprobante = e.target.value; pedirSiguienteFolio(); });
  $('#btnActualizarFolio')?.addEventListener('click', pedirSiguienteFolio);

  $('#cliente_id')?.addEventListener('change', (e)=>{
    state.cliente_id = e.target.value;
    $('#btnClienteEditar').disabled = !state.cliente_id;
    renderClienteResumen();
  });

  $('#btnClienteEditar')?.addEventListener('click', ()=> openModal('drawerCliente'));
  $('#drawerClienteClose')?.addEventListener('click', ()=> closeModal('drawerCliente'));

  // Guardar cliente (PUT)
  $('#drawerClienteSave')?.addEventListener('click', async ()=>{
    if (!state.cliente_id) return;
    const form = $('#drawerClienteBody form');
    if (!form) { closeModal('drawerCliente'); return; }
    const fd = new FormData(form);
    try {
      const r = await fetch(`${urls.clientesBase}/${state.cliente_id}`, { method:'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-HTTP-Method-Override': 'PUT' }, body: fd });
      if (!r.ok) throw new Error('Error al actualizar');
      // Opcional: refrescar detalle local si backend regresa JSON
      closeModal('drawerCliente');
    } catch(e) { alert('No se pudo actualizar el cliente'); }
  });

  $('#btnAgregarConcepto')?.addEventListener('click', ()=> agregarConcepto({}));

  // Delegación en conceptos (cambios y acciones)
  conceptosBody.addEventListener('input', (e)=>{
    const idx = e.target.getAttribute('data-idx');
    const field = e.target.getAttribute('data-field');
    if (idx==null || !field) return;
    const val = Number(e.target.value||0);
    if (!state.conceptos[idx]) return;
    state.conceptos[idx][field] = val;
    renderConceptos();
  });
  conceptosBody.addEventListener('click', (e)=>{
    const t = e.target;
    if (t.matches('[data-action="quitar-concepto"]')){
      const i = Number(t.getAttribute('data-idx'));
      state.conceptos.splice(i,1);
      renderConceptos();
    }
    if (t.closest('.concepto-impuesto-add')){
      const i = Number(t.closest('.concepto-impuesto-add').getAttribute('data-index'));
      idxConceptoForImp = i; openModal('modalImpuestoConcepto');
    }
    if (t.matches('[data-action="del-imp"]')){
      const i = Number(t.getAttribute('data-idx'));
      const j = Number(t.getAttribute('data-i'));
      (state.conceptos[i].impuestos||[]).splice(j,1);
      renderChipsImpuestos(i); recalcTotales();
    }
    if (t.matches('[data-action="editar-clave-prod-serv"]')){
      // Si existe Select2, aquí podrías abrir un selector; por ahora placeholder
      const nuevo = prompt('Clave ProdServ:', state.conceptos[Number(t.getAttribute('data-idx'))].claveProdServ||'');
      if (nuevo!=null) { state.conceptos[Number(t.getAttribute('data-idx'))].claveProdServ = nuevo; renderConceptos(); }
    }
    if (t.matches('[data-action="editar-clave-unidad"]')){
      const idx = Number(t.getAttribute('data-idx'));
      const nuevo = prompt('Clave Unidad:', state.conceptos[idx].claveUnidad||'');
      if (nuevo!=null) { state.conceptos[idx].claveUnidad = nuevo; renderConceptos(); }
    }
  });

  // Productos buscar
  $('#buscarProducto')?.addEventListener('input', (e)=>{
    const q = e.target.value.trim();
    clearTimeout(buscarDebounce);
    buscarDebounce = setTimeout(()=> buscarProductos(q), 300);
  });

  // Impuesto global modal open
  $('#btnImpuestoGlobalAdd')?.addEventListener('click', ()=> openModal('modalImpuestoGlobal'));

  // Eliminar global con delegación
  $('#impuestosGlobales')?.addEventListener('click', (e)=>{
    const t = e.target;
    if (t.matches('[data-action="del-imp-global"]')){
      const i = Number(t.getAttribute('data-i'));
      state.impuestosGlobales.splice(i,1);
      renderImpuestosGlobales();
      recalcTotales();
    }
  });

  // Acciones principales (placeholders)
  $('#btnPreview')?.addEventListener('click', ()=> alert('Preview pendiente de integrar'));
  $('#btnGuardar')?.addEventListener('click', ()=> alert('Guardar prefactura pendiente de integrar'));
  $('#btnTimbrar')?.addEventListener('click', ()=> alert('Timbrado pendiente de integrar'));

  // Init
  pedirSiguienteFolio();
})();

