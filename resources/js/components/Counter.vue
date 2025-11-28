<script lang="ts">


export default{
    name: 'Counter',
    data(){
        return {
            counter: 0
        }
    },
    methods:{
        tick(){
            this.counter += 1;
        }
    }
}
</script>

<template>
    <div>Counter is : {{counter}}</div>
    <button @click="tick()">add</button>
</template>

<style scoped></style>
