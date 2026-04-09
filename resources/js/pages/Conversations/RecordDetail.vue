<template>
  <Head title="谈话记录详情" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/conversations/records" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
          ← 返回列表
        </Link>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-4">
          谈话记录详情
        </h1>
      </div>

      <!-- Detail Card -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8">
        <div class="space-y-8">
          <!-- Basic Info -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                学生
              </h3>
              <p class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                {{ record.student.name }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                辅导员
              </h3>
              <p class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                {{ record.advisor.name }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                班级
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ record.class_model.name }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                学院
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ record.class_model.department.name }}
              </p>
            </div>
          </div>

          <hr class="border-gray-200 dark:border-gray-700" />

          <!-- Conversation Details -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                谈话形式
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ formatForm(record.conversation_form) }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                谈话方式
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ formatMethod(record.conversation_method) }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                谈话原因
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ formatReason(record.conversation_reason) }}
              </p>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
                谈话时间
              </h3>
              <p class="mt-2 text-gray-900 dark:text-white">
                {{ formatDate(record.conversation_at) }}
              </p>
            </div>
          </div>

          <hr class="border-gray-200 dark:border-gray-700" />

          <!-- Topic -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              谈话主题
            </h3>
            <p class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
              {{ record.topic }}
            </p>
          </div>

          <!-- Content -->
          <div v-if="record.content">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              谈话内容
            </h3>
            <div class="mt-2 bg-gray-50 dark:bg-gray-700 p-4 rounded text-gray-900 dark:text-white whitespace-pre-line">
              {{ record.content }}
            </div>
          </div>

          <!-- Location -->
          <div v-if="record.location">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              谈话地点
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white">
              {{ record.location }}
            </p>
          </div>

          <!-- Record Time -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              记录时间
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white">
              {{ formatDate(record.created_at) }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <Link
              :href="`/conversations/records/${record.id}/edit`"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500"
            >
              编辑
            </Link>

            <button
              @click="deleteRecord"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-red-300 dark:border-red-600 text-base font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900"
            >
              删除
            </button>

            <Link
              href="/conversations/records"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              返回
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
  record: Object,
})

const formatForm = (form) => {
  const forms = {
    'talk': '谈心',
    'consultation': '咨询',
    'sport': '运动',
    'meal': '用餐',
    'tea_break': '下午茶',
    'seminar': '研讨',
    'other': '其他',
  }
  return forms[form] || form
}

const formatMethod = (method) => {
  const methods = {
    'one_on_one': '一对一谈话',
    'one_on_many': '一对多谈话',
    'dorm_visit': '走访寝室',
    'class_meeting': '主题班会',
    'family_contact': '家校联系',
  }
  return methods[method] || method
}

const formatReason = (reason) => {
  const reasons = {
    'academic': '学业',
    'life': '生活',
    'psychology': '心理',
    'discipline': '纪律',
    'other': '其他',
  }
  return reasons[reason] || reason
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('zh-CN')
}

const deleteRecord = () => {
  if (confirm('确定要删除此记录吗？')) {
    router.delete(`/conversations/records/${record.id}`, {
      onSuccess: () => {
        router.visit('/conversations/records')
      },
    })
  }
}
</script>

