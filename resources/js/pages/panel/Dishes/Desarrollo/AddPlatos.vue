<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="platoDialog" :style="{ width: '600px' }" header="Registro de Platos" :modal="true">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-10">
                    <label for="name" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model.trim="plato.name" required maxlength="100" fluid />
                    <small v-if="submitted && !plato.name" class="text-red-500">El nombre es obligatorio.</small>
                    <small v-else-if="submitted && plato.name.length < 2" class="text-red-500">El nombre debe tener al
                        menos
                        2 caracteres.</small>
                    <small v-else-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
                </div>
                <div class="col-span-2">
                    <label for="state" class="block font-bold mb-3">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="plato.state" :binary="true" inputId="state" />
                        <Tag :value="plato.state ? 'Activo' : 'Inactivo'"
                            :severity="plato.state ? 'success' : 'danger'" />
                        <small v-if="submitted && plato.state === null" class="text-red-500">El estado es
                            obligatorio.</small>
                        <small v-else-if="serverErrors.state" class="text-red-500">{{ serverErrors.state[0] }}</small>
                    </div>
                </div>
                <div class="col-span-6">
                    <label for="price" class="block font-bold mb-3">Precio <span class="text-red-500">*</span></label>
                    <InputNumber id="price" v-model="plato.price" mode="currency" currency="USD" locale="en-US"
                        class="w-full" />
                    <small v-if="submitted && (!plato.price || plato.price <= 0)" class="text-red-500">El precio debe
                        ser
                        mayor que 0.</small>
                    <small v-else-if="serverErrors.price" class="text-red-500">{{ serverErrors.price[0] }}</small>
                </div>

                <div class="col-span-6">
                    <label for="quantity" class="block font-bold mb-3">Cantidad <span
                            class="text-red-500">*</span></label>
                    <InputNumber id="quantity" v-model="plato.quantity" :min="0" class="w-full" />
                    <small v-if="submitted && plato.quantity < 0" class="text-red-500">La cantidad no puede ser
                        negativa.</small>
                    <small v-else-if="serverErrors.quantity" class="text-red-500">{{ serverErrors.quantity[0] }}</small>
                </div>

                <div class="col-span-12">
                    <label for="category" class="block font-bold mb-3">Categoría <span
                            class="text-red-500">*</span></label>
                    <Select id="category" v-model="plato.idCategory" :options="categories" optionLabel="label"
                        optionValue="value" placeholder="Seleccionar categoría" class="w-full"
                        :loading="loadingCategories" />
                    <small v-if="submitted && !plato.idCategory" class="text-red-500">La categoría es
                        obligatoria.</small>
                    <small v-else-if="serverErrors.idCategory" class="text-red-500">{{ serverErrors.idCategory[0]
                        }}</small>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Guardar" icon="pi pi-check" @click="guardarPlato" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';
import Select from 'primevue/select';

const toast = useToast();
const emit = defineEmits(['plato-agregado']);
const submitted = ref(false);
const platoDialog = ref(false);
const loadingCategories = ref(false);
const serverErrors = ref({});
const categories = ref([]);

const plato = ref({
    name: '',
    price: 0,
    quantity: 0,
    idCategory: null,
    state: true
});

function resetPlato() {
    plato.value = {
        name: '',
        price: 0,
        quantity: 0,
        idCategory: null,
        state: true
    };
    serverErrors.value = {};
    submitted.value = false;
}

function openNew() {
    resetPlato();
    platoDialog.value = true;
}

function hideDialog() {
    platoDialog.value = false;
    resetPlato();
}

function guardarPlato() {
    submitted.value = true;
    serverErrors.value = {};

    axios.post('/plato', plato.value)
        .then(response => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Plato registrado', life: 3000 });
            hideDialog();
            emit('plato-agregado');
        })
        .catch(error => {
            if (error.response && error.response.status === 422) {
                serverErrors.value = error.response.data.errors || {};
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'No se pudo registrar el plato',
                    life: 3000
                });
            }
        });
}

function cargarCategorias() {
    loadingCategories.value = true;
    axios.get('/categoria')
        .then(response => {
            if (response.data && response.data.data) {
                categories.value = response.data.data.map(cat => ({
                    value: cat.id,
                    label: cat.name
                }));
            }
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las categorías', life: 3000 });
        })
        .finally(() => {
            loadingCategories.value = false;
        });
}

onMounted(() => {
    cargarCategorias();
});
</script>
