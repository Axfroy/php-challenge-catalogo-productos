const API_URL = '/productos';

function requiredElement(selector, root = document) {
    const element = root.querySelector(selector);

    if (element === null) {
        throw new Error(`No se encontró el elemento ${selector}.`);
    }

    return element;
}

const elements = {
    addProduct: requiredElement('#add-product'),
    productDialog: requiredElement('#product-dialog'),
    closeProductDialog: requiredElement('#close-product-dialog'),
    form: requiredElement('#product-form'),
    formTitle: requiredElement('#form-title'),
    save: requiredElement('#save'),
    cancelEdit: requiredElement('#cancel-edit'),
    feedback: requiredElement('#feedback'),
    loading: requiredElement('#loading'),
    empty: requiredElement('#empty'),
    error: requiredElement('#error'),
    errorMessage: requiredElement('#error-message'),
    table: requiredElement('#table-wrapper'),
    products: requiredElement('#products'),
    productCount: requiredElement('#product-count'),
    deleteDialog: requiredElement('#delete-dialog'),
    deleteProductName: requiredElement('#delete-product-name'),
    deleteError: requiredElement('#delete-error'),
    cancelDelete: requiredElement('#cancel-delete'),
    confirmDelete: requiredElement('#confirm-delete'),
    retry: requiredElement('#retry'),
};

const { form } = elements;
const inputs = form.elements;

const ars = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
const usd = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'USD' });

let products = [];
let editingId = null;
let deletingProduct = null;
let feedbackTimer = null;
let submitting = false;

async function request(url, { method = 'GET', body } = {}) {
    let response;
    const options = {
        method,
        headers: { Accept: 'application/json' },
    };

    if (body !== undefined) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    try {
        response = await fetch(url, options);
    } catch {
        throw new Error('No se pudo contactar con la API.');
    }

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(payload.message ?? 'La operación no pudo completarse.');
        error.fields = payload.errors ?? {};
        throw error;
    }

    return payload;
}

async function loadProducts() {
    showListState('loading');

    try {
        const response = await request(API_URL);
        const { data } = response ?? {};

        if (!Array.isArray(data)) {
            throw new Error('La API devolvió una respuesta inesperada.');
        }

        products = data;
        renderProducts();
        updateProductCount();
        showListState(products.length === 0 ? 'empty' : 'ready');
    } catch (error) {
        elements.errorMessage.textContent = error.message;
        elements.productCount.textContent = 'Sin datos';
        showListState('error');
    }
}

function showListState(state) {
    elements.loading.hidden = state !== 'loading';
    elements.empty.hidden = state !== 'empty';
    elements.error.hidden = state !== 'error';
    elements.table.hidden = state !== 'ready';
}

function updateProductCount() {
    const suffix = products.length === 1 ? 'producto' : 'productos';
    elements.productCount.textContent = `${products.length} ${suffix}`;
}

function renderProducts() {
    const rows = document.createDocumentFragment();

    for (const { id, nombre, descripcion, precio, precio_usd } of products) {
        const row = document.createElement('tr');
        row.dataset.id = String(id);

        appendCell(row, nombre, 'product-name', 'Producto');
        appendCell(row, descripcion ?? '—', 'description-cell', 'Descripción');
        appendCell(row, ars.format(precio), 'number', 'Precio ARS');
        appendCell(row, usd.format(precio_usd), 'number', 'Precio USD');

        const actions = document.createElement('td');
        actions.className = 'row-actions';
        actions.dataset.label = 'Acciones';
        actions.append(
            actionButton('Editar', 'edit', '', `Editar ${nombre}`),
            actionButton('Eliminar', 'delete', 'button--danger', `Eliminar ${nombre}`),
        );
        row.append(actions);
        rows.append(row);
    }

    elements.products.replaceChildren(rows);
}

function appendCell(row, value, className, label) {
    const cell = document.createElement('td');
    cell.className = className;
    cell.dataset.label = label;
    cell.textContent = value;
    row.append(cell);
}

function actionButton(label, action, className = '', accessibleLabel = label) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `button button--small ${className}`.trim();
    button.dataset.action = action;
    button.textContent = label;
    button.setAttribute('aria-label', accessibleLabel);

    return button;
}

function openCreateDialog() {
    resetForm();
    elements.productDialog.showModal();
    requestAnimationFrame(() => inputs.nombre.focus());
}

function openEditDialog({ id, nombre, descripcion, precio }) {
    editingId = id;
    elements.formTitle.textContent = 'Editar producto';
    inputs.nombre.value = nombre;
    inputs.descripcion.value = descripcion ?? '';
    inputs.precio.value = precio;
    clearFieldErrors();
    elements.productDialog.showModal();
    requestAnimationFrame(() => inputs.nombre.focus());
}

function openDeleteDialog(product) {
    const { nombre: productName } = product;

    deletingProduct = product;
    elements.deleteProductName.textContent = `“${productName}”`;
    elements.deleteError.hidden = true;
    elements.deleteError.textContent = '';
    elements.deleteDialog.showModal();
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearFieldErrors();

    if (submitting || !form.reportValidity()) {
        return;
    }

    const data = {
        nombre: inputs.nombre.value.trim(),
        descripcion: inputs.descripcion.value.trim() || null,
        precio: inputs.precio.value.trim(),
    };

    const creating = editingId === null;
    const url = creating ? API_URL : `${API_URL}/${editingId}`;
    const method = creating ? 'POST' : 'PUT';

    submitting = true;
    setBusy(elements.save, true, 'Guardando…', 'Guardar');

    try {
        const { data: savedProduct } = await request(url, { method, body: data });

        elements.productDialog.close();
        showFeedback(creating
            ? `Se creó “${savedProduct.nombre}”.`
            : `Se actualizó “${savedProduct.nombre}”.`);
        await loadProducts();
    } catch (error) {
        showFieldErrors(error.fields ?? {});
        showFeedback(error.message, true);
    } finally {
        submitting = false;
        setBusy(elements.save, false, 'Guardando…', 'Guardar');
    }
});

form.addEventListener('input', ({ target }) => {
    const { name: field } = target;

    if (field) {
        clearFieldError(field);
    }
});

elements.products.addEventListener('click', (event) => {
    const button = event.target.closest('[data-action]');

    if (button === null) {
        return;
    }

    const row = button.closest('tr');
    const productId = Number(row.dataset.id);
    const product = products.find(({ id }) => id === productId);

    if (product === undefined) {
        return;
    }

    const { action } = button.dataset;

    if (action === 'edit') {
        openEditDialog(product);
        return;
    }

    openDeleteDialog(product);
});

elements.confirmDelete.addEventListener('click', async () => {
    if (deletingProduct === null || elements.confirmDelete.disabled) {
        return;
    }

    const { id, nombre } = deletingProduct;
    setBusy(elements.confirmDelete, true, 'Eliminando…', 'Eliminar');
    elements.deleteError.hidden = true;

    try {
        await request(`${API_URL}/${id}`, { method: 'DELETE' });
        elements.deleteDialog.close();
        showFeedback(`Se eliminó “${nombre}”.`);
        await loadProducts();
    } catch (error) {
        elements.deleteError.textContent = error.message;
        elements.deleteError.hidden = false;
    } finally {
        setBusy(elements.confirmDelete, false, 'Eliminando…', 'Eliminar');
    }
});

function resetForm() {
    editingId = null;
    form.reset();
    elements.formTitle.textContent = 'Agregar producto';
    elements.save.textContent = 'Guardar';
    clearFieldErrors();
}

function showFeedback(message, error = false) {
    window.clearTimeout(feedbackTimer);
    elements.feedback.textContent = message;
    elements.feedback.classList.toggle('feedback--error', error);
    elements.feedback.hidden = false;
    feedbackTimer = window.setTimeout(() => {
        elements.feedback.hidden = true;
    }, error ? 7000 : 4500);
}

function showFieldErrors(errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const target = document.querySelector(`[data-error="${field}"]`);
        const input = form.elements[field];

        if (target !== null) {
            target.textContent = messages.join(' ');
        }

        if (input !== undefined) {
            input.setAttribute('aria-invalid', 'true');
        }
    }
}

function clearFieldError(field) {
    const target = document.querySelector(`[data-error="${field}"]`);
    const input = form.elements[field];

    if (target !== null) {
        target.textContent = '';
    }

    if (input !== undefined) {
        input.removeAttribute('aria-invalid');
    }
}

function clearFieldErrors() {
    document.querySelectorAll('[data-error]').forEach((element) => {
        element.textContent = '';
    });
    form.querySelectorAll('[aria-invalid]').forEach((element) => {
        element.removeAttribute('aria-invalid');
    });
}

function setBusy(button, busy, busyText, idleText) {
    button.disabled = busy;
    button.textContent = busy ? busyText : idleText;
}

const closeOnBackdrop = ({ target, currentTarget }) => {
    if (target === currentTarget) {
        currentTarget.close();
    }
};

elements.addProduct.addEventListener('click', openCreateDialog);
elements.closeProductDialog.addEventListener('click', () => elements.productDialog.close());
elements.cancelEdit.addEventListener('click', () => elements.productDialog.close());
elements.productDialog.addEventListener('close', resetForm);
elements.productDialog.addEventListener('click', closeOnBackdrop);

elements.cancelDelete.addEventListener('click', () => elements.deleteDialog.close());
elements.deleteDialog.addEventListener('close', () => {
    deletingProduct = null;
    elements.deleteError.hidden = true;
});
elements.deleteDialog.addEventListener('click', closeOnBackdrop);

elements.retry.addEventListener('click', loadProducts);

loadProducts();
