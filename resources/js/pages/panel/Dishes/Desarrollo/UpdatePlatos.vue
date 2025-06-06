<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';

const props = defineProps({
    visible: Boolean,
    PlatoId: [Number, String]
});
const emit = defineEmits(['update:visible', 'updated']);

const serverErrors = ref({});
const submitted = ref(false);
const toast = useToast();
const loading = ref(false);
const loadingCategories = ref(false);
const categories = ref([]);

const dialogVisible = ref(props.visible);
watch(() => props.visible, (val) => dialogVisible.value = val);
watch(dialogVisible, (val) => emit('update:visible', val));

const plato = ref({
    name: '',
    price: 0,
    quantity: 0,
    idCategory: null,
    state: false
});

watch(() => [props.visible, props.PlatoId], ([newVisible, newPlatoId]) => {
    if (newVisible && newPlatoId) {
        fetchPlato();
    }
}, { immediate: true });

const fetchPlato = async () => {
    loading.value = true;
    
    try {
        const response = await axios.get(`/plato/${props.PlatoId}`);
        
        // Reemplazar "data" con "dishes"
        if (response.data && response.data.dishes) {
            const data = response.data.dishes;
            plato.value = {
                name: data.name || '',
                price: parseFloat(data.price) || 0,
                quantity: data.quantity || 0,
                idCategory: data.idCategory || null,
                state: data.state === true
            };
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Formato de respuesta inesperado',
                life: 3000
            });
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar el plato',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    loadingCategories.value = true;
    try {
        const response = await axios.get('/categoria');
        if (response.data && response.data.data) {
            categories.value = response.data.data.map(cat => ({
                value: cat.id,
                label: cat.name
            }));
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar las categorías',
            life: 3000
        });
    } finally {
        loadingCategories.value = false;
    }
};

const updatePlato = async () => {
    submitted.value = true;
    serverErrors.value = {};

    if (!plato.value.name.trim()) {
        serverErrors.value.name = ['El nombre es requerido'];
        return;
    }

    if (plato.value.price <= 0) {
        serverErrors.value.price = ['El precio debe ser mayor que 0'];
        return;
    }

    if (plato.value.quantity < 0) {
        serverErrors.value.quantity = ['La cantidad no puede ser negativa'];
        return;
    }

    if (!plato.value.idCategory) {
        serverErrors.value.idCategory = ['La categoría es requerida'];
        return;
    }

    try {
        const platoData = {
            name: plato.value.name,
            price: plato.value.price,
            quantity: plato.value.quantity,
            idCategory: plato.value.idCategory,
            state: plato.value.state === true
        };

        await axios.put(`/plato/${props.PlatoId}`, platoData);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Plato actualizado correctamente',
            life: 3000
        });

        dialogVisible.value = false;
        emit('updated');
    } catch (error) {
        
        if (error.response && error.response.data?.errors) {
            serverErrors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: 'Revisa los campos e intenta nuevamente.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar el plato',
                life: 3000
            });
        }
    }
};

onMounted(() => {
    loadCategories();
});
</script>

<template>
    <Dialog v-model:visible="dialogVisible" header="Editar Plato" modal :closable="true" :closeOnEscape="true"
        :style="{ width: '600px' }">
        <div v-if="loading" class="flex justify-center p-4">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        <div v-else class="flex flex-col gap-4">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <label for="name" class="block font-bold mb-2">Nombre <span class="text-red-500">*</span></label>
                    <InputText
                        id="name"
                        v-model="plato.name"
                        required
                        maxlength="100"
                        class="w-full"
                        :class="{ 'p-invalid': serverErrors.name }"
                    />
                    <small v-if="serverErrors.name" class="p-error">{{ serverErrors.name[0] }}</small>
                </div>

                <div class="col-span-6">
                    <label for="price" class="block font-bold mb-2">Precio <span class="text-red-500">*</span></label>
                    <InputNumber
                        id="price"
                        v-model="plato.price"
                        :minFractionDigits="2"
                        :maxFractionDigits="2"
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        class="w-full"
                        :class="{ 'p-invalid': serverErrors.price }"
                    />
                    <small v-if="serverErrors.price" class="p-error">{{ serverErrors.price[0] }}</small>
                </div>

                <div class="col-span-6">
                    <label for="quantity" class="block font-bold mb-2">Cantidad <span class="text-red-500">*</span></label>
                    <InputNumber
                        id="quantity"
                        v-model="plato.quantity"
                        :min="0"
                        class="w-full"
                        :class="{ 'p-invalid': serverErrors.quantity }"
                    />
                    <small v-if="serverErrors.quantity" class="p-error">{{ serverErrors.quantity[0] }}</small>
                </div>

                <div class="col-span-10">
                    <label for="category" class="block font-bold mb-2">Categoría <span class="text-red-500">*</span></label>
                    <Select
                        id="category"
                        v-model="plato.idCategory"
                        :options="categories"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Seleccionar categoría"
                        class="w-full"
                        :class="{ 'p-invalid': serverErrors.idCategory }"
                        :loading="loadingCategories"
                    />
                    <small v-if="serverErrors.idCategory" class="p-error">{{ serverErrors.idCategory[0] }}</small>
                </div>

                <div class="col-span-2">
                    <label for="state" class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="plato.state" :binary="true" inputId="state" />
                        <Tag :value="plato.state ? 'Activo' : 'Inactivo'" :severity="plato.state ? 'success' : 'danger'" />
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" />
            <Button label="Guardar" icon="pi pi-check" @click="updatePlato" :loading="loading" />
        </template>
    </Dialog>
</template>
