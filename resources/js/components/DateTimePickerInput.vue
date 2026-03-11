<template>
    <input
        :id="id"
        ref="inputRef"
        type="text"
        class="form-control"
        :required="required"
        :disabled="disabled"
    >
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

const props = withDefaults(defineProps<{
    modelValue: string
    id?: string
    required?: boolean
    disabled?: boolean
    min?: string
}>(), {
    id: undefined,
    required: false,
    disabled: false,
    min: undefined,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const inputRef = ref<HTMLInputElement | null>(null)
let picker: flatpickr.Instance | null = null

function initializePicker(): void {
    if (!inputRef.value) {
        return
    }

    picker = flatpickr(inputRef.value, {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d\\TH:i',
        altInput: true,
        altFormat: 'Y. m. d H:i',
        allowInput: true,
        disableMobile: true,
        minDate: props.min,
        defaultDate: props.modelValue || undefined,
        onChange: (_, dateStr) => {
            emit('update:modelValue', dateStr)
        },
    })
}

onMounted(() => {
    initializePicker()
})

watch(
    () => props.modelValue,
    (value) => {
        if (!picker) {
            return
        }

        const current = picker.input.value
        if (current !== value) {
            picker.setDate(value || null, false, 'Y-m-d\\TH:i')
        }
    }
)

watch(
    () => props.min,
    (value) => {
        if (!picker) {
            return
        }

        picker.set('minDate', value ?? undefined)
    }
)

watch(
    () => props.disabled,
    (value) => {
        if (!picker || !inputRef.value) {
            return
        }

        inputRef.value.disabled = value
        if (picker.altInput) {
            picker.altInput.disabled = value
        }
    }
)

onBeforeUnmount(() => {
    picker?.destroy()
})
</script>

