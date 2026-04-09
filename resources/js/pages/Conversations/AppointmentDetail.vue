<template>
  <Head title="约谈详情" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/conversations/appointments" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
          ← 返回列表
        </Link>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-4">
          约谈详情
        </h1>
      </div>

      <!-- Detail Card -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8">
        <div class="space-y-6">
          <!-- Status -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              状态
            </h3>
            <p class="mt-2">
              <span
                :class="{
                  'px-3 py-1 rounded-full text-sm font-medium': true,
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': appointment.status === 'pending',
                  'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': appointment.status === 'confirmed',
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': appointment.status === 'completed',
                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': appointment.status === 'cancelled',
                }"
              >
                {{ formatStatus(appointment.status) }}
              </span>
            </p>
          </div>

          <hr class="border-gray-200 dark:border-gray-700" />

          <!-- Student Info -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              学生信息
            </h3>
            <div class="mt-2 space-y-1">
              <p class="text-lg font-medium text-gray-900 dark:text-white">
                {{ appointment.student.name }}
              </p>
              <p class="text-gray-600 dark:text-gray-400">
                邮箱: {{ appointment.student.email }}
              </p>
            </div>
          </div>

          <!-- Advisor Info -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              辅导员
            </h3>
            <div class="mt-2 space-y-1">
              <p class="text-lg font-medium text-gray-900 dark:text-white">
                {{ appointment.advisor.name }}
              </p>
              <p class="text-gray-600 dark:text-gray-400">
                邮箱: {{ appointment.advisor.email }}
              </p>
            </div>
          </div>

          <!-- Appointment Type -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              预约类型
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white">
              {{ formatType(appointment.appointment_type) }}
            </p>
          </div>

          <!-- Remarks -->
          <div v-if="appointment.remarks">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              备注
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white whitespace-pre-line">
              {{ appointment.remarks }}
            </p>
          </div>

          <!-- Appointment Time -->
          <div v-if="appointment.appointed_at">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              预约时间
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white">
              {{ formatDate(appointment.appointed_at) }}
            </p>
          </div>

          <!-- Request Time -->
          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">
              申请时间
            </h3>
            <p class="mt-2 text-gray-900 dark:text-white">
              {{ formatDate(appointment.created_at) }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <button
              v-if="appointment.status === 'pending'"
              @click="confirmAppointment"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500"
            >
              确认约谈
            </button>

            <button
              v-if="appointment.status !== 'completed'"
              @click="cancelAppointment"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-red-300 dark:border-red-600 text-base font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900"
            >
              取消
            </button>

            <Link
              href="/conversations/appointments"
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

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

const props = defineProps<{
  appointment: {
    id: number
    status: string
    appointment_type: string
    remarks?: string | null
    appointed_at?: string | null
    created_at: string
    student: { name: string; email: string }
    advisor: { name: string; email: string }
  }
}>()

const appointment = props.appointment

/*
defineProps({
  appointment: Object,
})
*/

const formatStatus = (status) => {
  const statuses = {
    'pending': '待确认',
    'confirmed': '已确认',
    'completed': '已完成',
    'cancelled': '已取消',
  }
  return statuses[status] || status
}

const formatType = (type) => {
  const types = {
    'talk': '谈心',
    'consultation': '咨询',
    'other': '其他',
  }
  return types[type] || type
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('zh-CN')
}

const confirmAppointment = () => {
  if (confirm('确认此约谈？')) {
    router.patch(`/conversations/appointments/${appointment.id}/confirm`, {}, {
      onSuccess: () => {
        router.visit('/conversations/appointments?status=confirmed')
      },
    })
  }
}

const cancelAppointment = () => {
  if (confirm('取消此约谈？')) {
    router.delete(`/conversations/appointments/${appointment.id}`, {
      onSuccess: () => {
        router.visit('/conversations/appointments')
      },
    })
  }
}
</script>

