<template>
  <div class="max-w-full mx-auto mt-8 p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">教职工一人一档</h2>
    <div v-if="loading">数据加载中...</div>
    <div v-else-if="error" class="text-red-500">{{ error }}</div>
    <div v-else-if="profile">
        <div>
          <h4 class="text-lg font-semibold text-gray-700 mb-4">警报</h4>
          <div v-for="alert in profile.alerts" :key="alert.id" 
               :class="{
                 'bg-yellow-100 text-yellow-800': alert.alert_level === 'Yellow',
                 'bg-orange-100 text-orange-800': alert.alert_level === 'Orange',
                 'bg-red-100 text-red-800': alert.alert_level === 'Red'}"
               class="rounded-lg p-3 mb-2">
            <p class="font-medium">级别: {{ alert.alert_level }}</p>
            <p>描述: {{ alert.message }}</p>
          </div>
        </div>
      <div class="mb-4 border-b pb-2">
        <div><span class="font-medium">工号：</span>{{ profile.id }}</div>
        <div><span class="font-medium">姓名：</span>{{ profile.name }}</div>
        <div><span class="font-medium">部门：</span>{{ profile.department }}</div>
        <div><span class="font-medium">岗位：</span>{{ profile.position }}</div>
        <div><span class="font-medium">身份类型：</span>{{ profile.type }}</div>
        <div><span class="font-medium">手机号：</span>{{ profile.phone }}</div>
        <div><span class="font-medium">状态：</span>{{ profile.status }}</div>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-2">档案信息</h3>
        <p>下方可以通过切换显示详情：</p>
        <tabs>
          <tab title="基本信息">
            <div>
              <div><span class="font-medium">工号：</span>{{ profile.id }}</div>
              <div><span class="font-medium">姓名：</span>{{ profile.name }}</div>
              <div><span class="font-medium">部门：</span>{{ profile.department }}</div>
              <div><span class="font-medium">岗位：</span>{{ profile.position }}</div>
              <div><span class="font-medium">状态：</span>{{ profile.status }}</div>
            </div>
          </tab>
          <tab title="奖惩信息">
            奖惩记录: {{ profile.awards_punishments.length }} 条
          </tab>
          <tab title="预警进展">
            预警整改: {{ profile.warnings.length }} 条
          </tab>
        </tabs>
        <div>师德承诺：{{ profile.ethics_promise || '暂无' }}</div>
        <div>学习记录：{{ profile.learning.length > 0 ? profile.learning.length+' 条' : '暂无' }}</div>
        <div>考核: {{ profile.evaluation.length > 0 ? profile.evaluation.length+' 条' : '暂无' }}</div>
        <div>教学规范: {{ profile.teaching_norms.length > 0 ? profile.teaching_norms.length+' 条' : '暂无' }}</div>
        <div>学术诚信: {{ profile.academic_integrity.length > 0 ? profile.academic_integrity.length+' 条' : '暂无' }}</div>
        <div>奖惩信息: {{ profile.awards_punishments.length > 0 ? profile.awards_punishments.length+' 条' : '暂无' }}</div>
        <div>投诉举报: {{ profile.complaints.length > 0 ? profile.complaints.length+' 条' : '暂无' }}</div>
        <div>预警整改: {{ profile.warnings.length > 0 ? profile.warnings.length+' 条' : '暂无' }}</div>
        <div>年鉴鉴定: {{ profile.identification || '暂无' }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watchEffect } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

const loading = ref(true);
const error = ref(null);
const profile = ref(null);

// Configure Laravel Echo for real-time updates
const echo = new Echo({
  broadcaster: 'pusher',
  key: 'your-pusher-key', // Replace with real Pusher Key
  cluster: 'your-cluster',
  encrypted: true,
});

echo.channel(`teacher-scores`)
  .listen('.ScoreUpdated', (event) => {
    if (event.teacherId === testId) {
      profile.value.scores = event.updatedScores;
    }
  });

// 测试ID，实际应由登录人或URL参数如route传入
const testId = '20230001';

onMounted(async () => {
  try {
    const res = await axios.get(`/ethics/profile/${testId}`);
    profile.value = res.data;
  } catch (err) {
    error.value = err.response?.data?.error || '加载失败';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
</style>
