<script setup lang="ts">
import Counter from '@/components/Counter.vue';
import Ad from '@/components/Ad.vue';
import {ref} from 'vue';

let coupon = ref("test");
</script>

<template>
    test12
    <counter></counter>

    <input type="text" v-model="coupon">
    <input type="text" v-model="coupon">
    <ad></ad>
</template>

<style scoped></style>
