<script lang="ts">
import axios from 'axios';
export default {
    name: 'ProjectCreate',
    data(){
        return {
            name: '',
            description: '',
            errors: {}
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
                this.errors = err.response.data.errors;
            });
        }
    }
}

</script>

<template>

    <form method="post" action="/projects" @submit.prevent="CreateProject" class="max-w-md mx-auto p-6 bg-white rounded-lg shadow-md">

        <!-- 项目名称输入框 -->
        <div class="mb-5">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Project Name:</label>
            <input
                type="text"
                id="name"
                name="name"
                v-model="name"
                class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none
                   @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                placeholder="Enter project name"
            >
            <!-- 错误提示（保留原逻辑，优化样式） -->
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
        </div>

        <!-- 项目描述输入框 -->
        <div class="mb-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Project Description</label>
            <input
                type="text"
                id="description"
                name="description"
                v-model="description"
                required
                class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none"
                placeholder="Enter project description"
            >
        </div>

        <!-- 提交按钮 -->
        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
               active:scale-95 transition-all duration-200 font-medium"
        >
            Create Project
        </button>
    </form>
</template>

<style scoped></style>
