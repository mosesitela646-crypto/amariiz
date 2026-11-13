// Configuration API AMARIIZ.
const API_BASE = window.AMARIIZ_API_BASE || new URL('../../../backend/public/api/v1', document.currentScript.src).href.replace(/\/$/, '');
let AMARIIZ_PRODUCTS = [];

const fc = value => new Intl.NumberFormat('fr-FR').format(Number(value || 0)) + ' FC';
const token = () => localStorage.getItem('amariiz_token') || '';
const currentUser = () => {
  try { return JSON.parse(localStorage.getItem('amariiz_user') || 'null'); }
  catch { return null; }
};
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({
  '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
}[c]));
const dateLabel = value => value ? new Date(String(value).replace(' ', 'T')).toLocaleString('fr-FR') : '—';
const statusLabel = value => ({
  a_preparer:'À préparer', en_livraison:'En livraison', livree:'Livrée', annulee:'Annulée'
}[value] || value || '—');

async function api(path, options = {}) {
  const headers = {'Content-Type':'application/json', ...(options.headers || {})};
  if (token()) headers.Authorization = 'Bearer ' + token();
  const response = await fetch(API_BASE + path, {
    credentials:'include', ...options, headers
  });
  const json = await response.json().catch(() => ({success:false,message:'Réponse serveur invalide.'}));
  if (!response.ok || !json.success) {
    const error = new Error(json.message || 'Une erreur est survenue.');
    error.status = response.status;
    throw error;
  }
  return json.data;
}

const imageFor = variant => {
  const key = [variant.composition, variant.flavor, variant.format].join('-');
  const files = {
    'avec_sucre-vanille-petit':'01-yaourt-vanille-avec-sucre-250ml.webp',
    'sans_sucre-vanille-petit':'02-yaourt-vanille-sans-sucre-250ml.webp',
    'avec_sucre-banane-petit':'03-yaourt-banane-avec-sucre-250ml.webp',
    'sans_sucre-banane-petit':'04-yaourt-banane-sans-sucre-250ml.webp',
    'avec_sucre-vanille-grand':'05-yaourt-vanille-avec-sucre-500ml.webp',
    'sans_sucre-vanille-grand':'06-yaourt-vanille-sans-sucre-500ml.webp',
    'avec_sucre-banane-grand':'07-yaourt-banane-avec-sucre-500ml.webp',
    'sans_sucre-banane-grand':'08-yaourt-banane-sans-sucre-500ml.webp'
  };
  return 'assets/images/' + (files[key] || 'catalogue-amariiz-reference.svg');
};
function productFrom(variant) {
  return {
    id:Number(variant.variant_id), name:variant.name, flavour:variant.flavor,
    size:variant.format === 'petit' ? 'Petit format' : 'Grand format',
    volume:variant.format === 'petit' ? '250 ml' : '500 ml',
    price:Number(variant.price), image:imageFor(variant),
    tag:variant.composition === 'avec_sucre' ? 'Avec sucre' : 'Sans sucre',
    composition:variant.composition, format:variant.format
  };
}
async function loadProducts() {
  const data = await api('/catalogue/products');
  AMARIIZ_PRODUCTS = data.map(productFrom);
  return AMARIIZ_PRODUCTS;
}
function prefix() { return location.pathname.includes('/pages/') ? '../' : ''; }
function card(product) {
  return '<div class="col-6 col-lg-3"><article class="product-card"><div class="product-image"><img src="' +
    prefix() + esc(product.image) + '" alt="' + esc(product.name) + ' ' + esc(product.flavour) +
    '"></div><div class="d-flex justify-content-between align-items-center mt-3"><span class="tag">' +
    esc(product.tag) + '</span><span class="small-muted">' + esc(product.volume) +
    '</span></div><h5>' + esc(product.name) + '</h5><div class="product-meta">' +
    esc(product.flavour) + ' · ' + esc(product.size) +
    '</div><div class="d-flex align-items-center justify-content-between mt-3"><span class="product-price">' +
    fc(product.price) + '</span><button class="btn btn-brand btn-sm add-cart" data-id="' +
    product.id + '"><i class="bi bi-plus-lg"></i></button></div></article></div>';
}
async function addToCart(id, quantity = 1) {
  return api('/cart/items', {
    method:'POST',
    body:JSON.stringify({product_variant_id:Number(id),quantity:Number(quantity)})
  });
}

document.addEventListener('click', async event => {
  const button = event.target.closest('.add-cart');
  if (!button) return;
  try {
    await addToCart(button.dataset.id);
    button.innerHTML = '<i class="bi bi-check2"></i>';
  } catch (error) { alert(error.message); }
  setTimeout(() => { button.innerHTML = '<i class="bi bi-plus-lg"></i>'; }, 850);
});

async function initFeatured() {
  const target = document.getElementById('featuredProducts');
  if (!target) return;
  try {
    await loadProducts();
    target.innerHTML = AMARIIZ_PRODUCTS.slice(0,4).map(card).join('');
  } catch (error) {
    target.innerHTML = '<p class="text-danger">Catalogue indisponible : ' + esc(error.message) + '</p>';
  }
}

function safeReturnPage() {
  const value = new URLSearchParams(location.search).get('redirect');
  return ['commander.html','commandes.html','admin.html'].includes(value) ? value : '';
}
async function initLogin() {
  const form = document.getElementById('loginForm');
  if (!form) return;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const output = form.querySelector('.form-feedback');
    try {
      const data = await api('/auth/login', {
        method:'POST',
        body:JSON.stringify(Object.fromEntries(new FormData(form)))
      });
      localStorage.setItem('amariiz_token', data.token);
      localStorage.setItem('amariiz_user', JSON.stringify(data.user));
      const requested = safeReturnPage();
      if (requested === 'commander.html') location.href = requested;
      else if (data.user.role === 'admin') location.href = 'admin.html';
      else location.href = requested === 'commandes.html' ? requested : 'commandes.html';
    } catch (error) { output.textContent = error.message; }
  });
}
async function initRegister() {
  const form = document.getElementById('registerForm');
  if (!form) return;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const output = form.querySelector('.form-feedback');
    try {
      const data = await api('/auth/register', {
        method:'POST',
        body:JSON.stringify(Object.fromEntries(new FormData(form)))
      });
      localStorage.setItem('amariiz_token', data.token);
      localStorage.setItem('amariiz_user', JSON.stringify(data.user));
      location.href = safeReturnPage() === 'commander.html' ? 'commander.html' : 'commandes.html';
    } catch (error) { output.textContent = error.message; }
  });
}
async function initHistory() {
  const body = document.getElementById('ordersBody');
  if (!body) return;
  if (!body.dataset.reviewsBound) {
    body.dataset.reviewsBound = '1';
    body.addEventListener('submit', async event => {
      const form = event.target.closest('[data-review-form]');
      if (!form) return;
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      const payload = Object.fromEntries(new FormData(form));
      payload.rating = Number(payload.rating);
      try {
        await api('/orders/' + form.dataset.reviewForm + '/review', {
          method:'POST', body:JSON.stringify(payload)
        });
        await initHistory();
      } catch (error) {
        const feedback = form.querySelector('.review-feedback');
        feedback.textContent = error.message;
        button.disabled = false;
      }
    });
  }
  try {
    const orders = await api('/orders/mine');
    body.innerHTML = orders.length ? orders.map(order => {
      let review = '—';
      if (order.review_rating) {
        review = '<span class="review-stars">' + '★'.repeat(Math.max(0,Math.min(5,Number(order.review_rating)))) +
          '</span> ' + esc(order.review_rating) + '/5' +
          (order.review_comment ? '<small>' + esc(order.review_comment) + '</small>' : '');
      } else if (order.status === 'livree') {
        review = '<details><summary class="text-primary fw-semibold">Noter cette commande</summary>' +
          '<form class="mt-2" data-review-form="' + Number(order.id) + '">' +
          '<label class="form-label small" for="review-rating-' + Number(order.id) + '">Votre note</label>' +
          '<select class="form-select form-select-sm mb-2" id="review-rating-' + Number(order.id) + '" name="rating" required>' +
          '<option value="5">5 — Excellent</option><option value="4">4 — Très bien</option><option value="3">3 — Bien</option>' +
          '<option value="2">2 — Moyen</option><option value="1">1 — Décevant</option></select>' +
          '<textarea class="form-control form-control-sm mb-2" name="comment" rows="2" maxlength="1000" placeholder="Votre commentaire (facultatif)"></textarea>' +
          '<button class="btn btn-sm btn-primary" type="submit">Envoyer mon avis</button><small class="review-feedback text-danger d-block mt-1"></small></form></details>';
      }
      return '<tr><td>#' + esc(order.id) + '<small>' + esc(dateLabel(order.created_at)) + '</small></td>' +
        '<td>' + esc(order.city_name) + '</td><td>' + fc(order.total) + '</td>' +
        '<td><span class="status">' + esc(statusLabel(order.status)) + '</span></td><td>' + review + '</td></tr>';
    }).join('') : '<tr><td colspan="5">Aucune commande enregistrée.</td></tr>';
  } catch (error) {
    body.innerHTML = '<tr><td colspan="5">' + esc(error.message) + '</td></tr>';
  }
}

async function initCommander() {
  const form = document.getElementById('orderForm');
  if (!form) return;
  const composition = document.getElementById('composition');
  const flavour = document.getElementById('flavour');
  const format = document.getElementById('format');
  const variant = document.getElementById('variant');
  const city = document.getElementById('city');
  const quantity = document.getElementById('quantity');
  const selectedProduct = document.getElementById('selectedProduct');
  const output = form.querySelector('.form-feedback');

  if (!token()) {
    const notice = document.createElement('div');
    notice.className = 'alert alert-warning mb-4';
    notice.innerHTML = 'Pour enregistrer et suivre votre commande, connectez-vous ou créez un compte avant la validation. <a href="connexion.html?redirect=commander.html">Se connecter</a> · <a href="inscription.html?redirect=commander.html">Créer un compte</a>.';
    form.querySelector('.order-form-heading')?.after(notice);
  }

  let products = [];
  function refreshVariant() {
    const product = products.find(item =>
      item.composition === composition.value &&
      item.flavour === flavour.value &&
      item.format === format.value
    );
    if (!product) {
      variant.value = '';
      selectedProduct.textContent = 'Cette combinaison n’est pas disponible. Choisissez une autre option.';
      selectedProduct.classList.add('is-unavailable');
      return;
    }
    variant.value = String(product.id);
    selectedProduct.classList.remove('is-unavailable');
    selectedProduct.textContent = product.name + ' · ' + product.flavour + ' · ' +
      product.volume + ' — ' + fc(product.price) + ' / unité';
  }

  try {
    const [productData, cities, promotions] = await Promise.all([
      loadProducts(), api('/cities'), api('/promotions').catch(() => [])
    ]);
    products = productData;
    composition.value = products.some(p => p.composition === 'avec_sucre') ? 'avec_sucre' : (products[0]?.composition || 'avec_sucre');
    flavour.value = products.some(p => p.flavour === 'vanille') ? 'vanille' : (products[0]?.flavour || 'vanille');
    format.value = products.some(p => p.format === 'petit') ? 'petit' : (products[0]?.format || 'petit');
    city.innerHTML = cities.map(item =>
      '<option value="' + Number(item.id) + '">' + esc(item.name) + ' — livraison ' + fc(item.delivery_fee) + '</option>'
    ).join('');
    refreshVariant();
    const promoList = document.getElementById('availablePromotions');
    if (promoList && promotions.length) {
      promoList.innerHTML = 'Promotions disponibles : ' + promotions.map(p =>
        '<span class="badge bg-light text-dark border me-1">' + esc(p.code) + ' · ' +
        (p.discount_type === 'percent' ? esc(p.discount_value) + '%' : fc(p.discount_value)) + '</span>'
      ).join('');
    }
  } catch (error) {
    selectedProduct.textContent = 'Impossible de charger les produits ou les villes. ' + error.message;
    selectedProduct.classList.add('is-unavailable');
    output.textContent = error.message;
  }

  [composition,flavour,format].forEach(element => element.addEventListener('change',refreshVariant));
  form.addEventListener('submit', async event => {
    event.preventDefault();
    output.textContent = '';
    if (!token()) {
      output.classList.remove('text-success');
      output.classList.add('text-danger');
      output.innerHTML = 'Connectez-vous pour valider votre commande. <a href="connexion.html?redirect=commander.html">Se connecter</a>.';
      return;
    }
    if (!variant.value) { output.textContent = 'Veuillez choisir une combinaison de yaourt disponible.'; return; }
    if (!Number.isInteger(Number(quantity.value)) || Number(quantity.value) < 1) {
      output.textContent = 'La quantité doit être au moins égale à 1.';
      return;
    }
    try {
      await addToCart(variant.value,quantity.value);
      const data = Object.fromEntries(new FormData(form));
      data.city_id = Number(data.city_id);
      const result = await api('/orders',{method:'POST',body:JSON.stringify(data)});
      output.classList.remove('text-danger');
      output.classList.add('text-success');
      output.textContent = 'Commande créée. Ouverture de WhatsApp…';
      window.open(result.whatsapp_url,'_blank');
    } catch (error) {
      output.classList.remove('text-success');
      output.classList.add('text-danger');
      if (error.status === 401) {
        output.innerHTML = 'Votre connexion a expiré. <a href="connexion.html?redirect=commander.html">Reconnectez-vous</a>.';
      } else {
        output.textContent = error.message;
      }
    }
  });
}

async function initAdminDashboard() {
  const root = document.getElementById('adminApp');
  if (!root) return;
  const authMessage = document.getElementById('adminAuthMessage');
  if (!token()) {
    authMessage.innerHTML = 'Connectez-vous avec un compte administrateur pour accéder aux données. <a href="connexion.html?redirect=admin.html">Se connecter</a>.';
    return;
  }
  if (currentUser()?.role !== 'admin') {
    authMessage.textContent = 'Ce compte ne possède pas les droits administrateur.';
    return;
  }
  authMessage.textContent = '';
  const $ = id => document.getElementById(id);
  const setHtml = (id, html) => { if ($(id)) $(id).innerHTML = html; };
  const setText = (id, value) => { if ($(id)) $(id).textContent = value; };
  const from = $('adminFrom');
  const to = $('adminTo');
  const today = new Date();
  const iso = d => d.toISOString().slice(0,10);
  if (!from.value) from.value = iso(new Date(today.getFullYear(),today.getMonth(),1));
  if (!to.value) to.value = iso(today);

  async function loadDashboard() {
    const data = await api('/admin/dashboard?from=' + encodeURIComponent(from.value) + '&to=' + encodeURIComponent(to.value));
    const s = data.summary || {};
    setText('kpiSales',fc(s.sales));
    setText('kpiOrders',s.orders_count || 0);
    setText('kpiClients',s.clients_count || 0);
    setText('kpiPending',s.pending_orders || 0);
    setText('kpiDelivery',s.delivery_orders || 0);
    setText('kpiDelivered',s.delivered_orders || 0);
    setText('kpiCancelled',s.cancellations || 0);
    setText('kpiDiscounts',fc(s.discounts));
    setHtml('topProducts',(data.top_products || []).map(item =>
      '<li><span>' + esc(item.name) + '</span><strong>' + esc(item.quantity) + '</strong></li>'
    ).join('') || '<li>Aucune vente sur cette période.</li>');
    setHtml('topCities',(data.top_cities || []).map(item =>
      '<li><span>' + esc(item.name) + '</span><strong>' + esc(item.orders_count) + '</strong></li>'
    ).join('') || '<li>Aucune commande sur cette période.</li>');
  }

  async function loadOrders() {
    const orders = await api('/seller/orders');
    setHtml('adminOrders',orders.map(order => '<tr>' +
      '<td>#' + esc(order.id) + '<small>' + esc(dateLabel(order.created_at)) + '</small></td>' +
      '<td><strong>' + esc(order.customer_name) + '</strong><small>' + esc(order.phone) + '</small></td>' +
      '<td>' + esc(order.city_name) + '</td><td>' + fc(order.total) + '</td>' +
      '<td><span class="admin-status status-' + esc(order.status) + '">' + esc(statusLabel(order.status)) + '</span></td>' +
      '<td><select class="form-select form-select-sm" data-status-select="' + Number(order.id) + '">' +
      ['a_preparer','en_livraison','livree','annulee'].map(st => '<option value="' + st + '"' + (st===order.status?' selected':'') + '>' + esc(statusLabel(st)) + '</option>').join('') +
      '</select></td><td><button class="btn btn-sm btn-outline-primary" data-save-status="' + Number(order.id) + '">Enregistrer</button> ' +
      '<button class="btn btn-sm btn-outline-secondary" data-order-detail="' + Number(order.id) + '">Détail</button></td></tr>'
    ).join('') || '<tr><td colspan="7">Aucune commande enregistrée.</td></tr>');
  }

  async function loadClients() {
    const clients = await api('/admin/clients');
    setHtml('adminClients',clients.map(client => '<tr><td><strong>' + esc(client.name) + '</strong><small>' + esc(client.email) + '</small></td>' +
      '<td>' + esc(client.phone) + '</td><td>' + esc(client.orders_count) + '</td><td>' + fc(client.orders_value) +
      '</td><td>' + esc(dateLabel(client.last_order_at)) + '</td></tr>'
    ).join('') || '<tr><td colspan="5">Aucun compte client enregistré.</td></tr>');
  }

  async function loadReviews() {
    const reviews = await api('/admin/reviews');
    setHtml('adminReviews',reviews.map(review => '<tr><td>#' + esc(review.order_id) + '</td>' +
      '<td>' + esc(review.customer_name) + '<small>' + esc(review.phone) + '</small></td>' +
      '<td><span class="review-stars">' + '★'.repeat(Math.max(0,Math.min(5,Number(review.rating)))) + '</span> ' + esc(review.rating) + '/5</td>' +
      '<td>' + esc(review.comment || 'Aucun commentaire') + '</td></tr>'
    ).join('') || '<tr><td colspan="4">Aucun avis enregistré.</td></tr>');
  }

  async function loadStock() {
    const stock = await api('/admin/stock/low');
    setHtml('adminStock',stock.map(item => '<tr><td>' + esc(item.name) + '</td><td>' +
      esc(item.composition) + ' · ' + esc(item.flavor) + ' · ' + esc(item.format) +
      '</td><td><strong>' + esc(item.stock_qty) + '</strong></td><td>' + esc(item.low_stock_threshold) + '</td></tr>'
    ).join('') || '<tr><td colspan="4">Aucune alerte de stock faible.</td></tr>');
  }

  async function loadPromotions() {
    const promotions = await api('/admin/promotions');
    setHtml('adminPromotions',promotions.map(p => '<tr><td><strong>' + esc(p.code) + '</strong><small>' + esc(p.title) + '</small></td>' +
      '<td>' + (p.discount_type === 'percent' ? esc(p.discount_value) + '%' : fc(p.discount_value)) +
      '</td><td>' + esc(dateLabel(p.starts_at)) + '<small>jusqu’au ' + esc(dateLabel(p.ends_at)) + '</small></td>' +
      '<td>' + esc(p.uses_count) + (p.max_uses ? ' / ' + esc(p.max_uses) : ' / ∞') + '</td>' +
      '<td><span class="admin-status ' + (Number(p.is_active) ? 'status-livree' : 'status-annulee') + '">' +
      (Number(p.is_active) ? 'Active' : 'Désactivée') + '</span></td>' +
      '<td><button class="btn btn-sm btn-outline-primary" data-promo-toggle="' + Number(p.id) + '" data-active="' + (Number(p.is_active)?'1':'0') + '">' +
      (Number(p.is_active) ? 'Désactiver' : 'Activer') + '</button></td></tr>'
    ).join('') || '<tr><td colspan="6">Aucune promotion créée.</td></tr>');
  }

  async function loadAll() {
    setText('adminRefreshStatus','Actualisation…');
    try {
      await Promise.all([loadDashboard(),loadOrders(),loadClients(),loadReviews(),loadStock(),loadPromotions()]);
      setText('adminRefreshStatus','Données chargées depuis la base de données.');
    } catch (error) {
      setText('adminRefreshStatus',error.message);
      if (error.status === 401 || error.status === 403) authMessage.textContent = error.message;
    }
  }

  root.addEventListener('click',async event => {
    const save = event.target.closest('[data-save-status]');
    const detail = event.target.closest('[data-order-detail]');
    const toggle = event.target.closest('[data-promo-toggle]');
    try {
      if (save) {
        const id = save.dataset.saveStatus;
        const selected = root.querySelector('[data-status-select="' + id + '"]');
        await api('/seller/orders/' + id + '/status',{method:'PATCH',body:JSON.stringify({status:selected.value})});
        await Promise.all([loadOrders(),loadDashboard()]);
        setText('adminRefreshStatus','Statut de la commande #' + id + ' mis à jour.');
      } else if (detail) {
        const order = await api('/seller/orders/' + detail.dataset.orderDetail);
        const items = (order.items || []).map(item => '<li>' + esc(item.name) + ' · ' +
          esc(item.composition) + ' · ' + esc(item.flavor) + ' · ' + esc(item.format) +
          ' × ' + esc(item.quantity) + ' — ' + fc(item.unit_price) + '</li>').join('');
        const history = (order.history || []).map(item => '<li>' + esc(statusLabel(item.status)) + ' — ' +
          esc(dateLabel(item.created_at)) + ' (' + esc(item.changed_by_name) + ')</li>').join('');
        setHtml('orderDetail','<div class="detail-head"><strong>Commande #' + esc(order.id) + ' · ' + esc(order.customer_name) +
          '</strong><button type="button" class="btn-close" data-close-detail aria-label="Fermer"></button></div>' +
          '<p>' + esc(order.phone) + ' · ' + esc(order.city_name) + ' · ' + fc(order.total) + '</p>' +
          '<h6>Articles</h6><ul>' + items + '</ul><h6>Historique des statuts</h6><ul>' + history + '</ul>');
      } else if (toggle) {
        await api('/admin/promotions/' + toggle.dataset.promoToggle,{
          method:'PATCH',body:JSON.stringify({is_active:toggle.dataset.active !== '1'})
        });
        await loadPromotions();
      } else if (event.target.closest('[data-close-detail]')) {
        setHtml('orderDetail','');
      }
    } catch (error) { setText('adminRefreshStatus',error.message); }
  });

  const promoForm = $('promotionForm');
  promoForm?.addEventListener('submit',async event => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(promoForm));
    data.discount_value = Number(data.discount_value);
    data.max_uses = data.max_uses ? Number(data.max_uses) : null;
    try {
      await api('/admin/promotions',{method:'POST',body:JSON.stringify(data)});
      promoForm.reset();
      await loadPromotions();
      setText('adminRefreshStatus','Promotion enregistrée.');
    } catch (error) { setText('adminRefreshStatus',error.message); }
  });

  $('adminApplyDates')?.addEventListener('click',loadAll);
  $('adminReload')?.addEventListener('click',loadAll);
  setText('adminUserName',currentUser()?.name || 'Administrateur');
  await loadAll();
}

document.addEventListener('DOMContentLoaded', () => {
  const year = document.getElementById('year');
  if (year) year.textContent = new Date().getFullYear();
  initFeatured();
  initLogin();
  initRegister();
  initHistory();
  initCommander();
  initAdminDashboard();
});
