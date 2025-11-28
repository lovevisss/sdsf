<script lang="ts">
import axios from 'axios';
export default {
    name: 'ProjectCreate',
    data(){
        return {
            name: '',
            description: ''
        }
    },
    methods:{
        CreateProject(){
            console.log("创建项目" + this.name + this.description);
            axios.post('/projects', {
                name: this.name,
                description: this.description
            }).then((res)=>{
                console.log(res.data);
                window.location.href = '/projects/' + res.data.id;
            }).catch((err)=>{
                console.log(err);
            });
        }
    }
}

</script>

<template>
    <form method="post" action="/projects" @submit.prevent="CreateProject">
        <div class="control">
            <label for="name" class="label">Project Name:</label>
            <input type="text" id="name" name="name" class="input" required v-model="name">
        </div>
        <div class="control">
            <label for="description">Project Description</label>
            <input type="text" id="description" name="description" class="input" required v-model="description">
        </div>
        <div class="control">
            <button class="button is-primary">Create</button>
        </div>
    </form>
</template>

<style scoped></style>
