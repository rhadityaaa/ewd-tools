<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { DateFormatter, type DateValue, getLocalTimeZone, parseDate } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modelValue?: DateValue | string | undefined;
    placeholder?: string;
}>();

const parsedDate = computed(() => {
    if (!props.modelValue) return null;

    try {
        if (typeof props.modelValue === 'string') {
            return parseDate(props.modelValue);
        } else {
            return props.modelValue;
        }
    } catch (error) {
        console.error('Invalid date format:', props.modelValue);
        return null;
    }
});

const emit = defineEmits<{
    (e: 'update:modelValue', date: string): void;
}>();

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
});

const value = ref<DateValue | null>(parsedDate.value);

watch(
    () => parsedDate.value,
    (newVal) => {
        value.value = newVal;
    },
);

watch(
    value,
    (newValue) => {
        if (newValue) {
            emit('update:modelValue', newValue.toString());
        }
    },
    { immediate: true },
);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" :class="cn('w-[280px] justify-start text-left font-normal', !value && 'text-muted-foreground')">
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ value ? df.format(value.toDate(getLocalTimeZone())) : 'Pick a date' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar v-model="value" initial-focus />
        </PopoverContent>
    </Popover>
</template>
