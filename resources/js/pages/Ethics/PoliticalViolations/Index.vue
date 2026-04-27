<template>
  <Head title="思想政治素养违规手工登记" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">思想政治素养违规手工登记</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">总分100分中的思想政治素养25分，按违规类型手工登记扣分并自动汇总。</p>
          </div>
          <Link href="/ethics/dashboard" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回治理中心</Link>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">当前人员年度记录数</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ selectedSummary?.violation_count ?? 0 }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">当前人员年度累计扣分</p>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ selectedSummary?.total_deduction ?? 0 }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">当前人员剩余分值（满分25）</p>
            <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">{{ selectedSummary?.remaining_score ?? 25 }}</p>
          </div>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-400">
          {{ selectedSummary ? `当前查看：${selectedSummary.staff_no} - ${selectedSummary.staff_name}` : '请先搜索并选择一个人员，再查看该人员的25分扣分情况。' }}
          <Link
            v-if="selectedSummary?.profile_url"
            :href="selectedSummary.profile_url"
            class="ml-2 text-blue-600 hover:underline dark:text-blue-400"
          >
            查看历年记录
          </Link>
        </p>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700 dark:border-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
          可选 Staff 人员总数：{{ props.staffOptionsCount }}
          <span v-if="props.staffOptionsError" class="ml-2 text-red-600 dark:text-red-400">{{ props.staffOptionsError }}</span>
        </div>

        <form class="grid grid-cols-1 gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-2 lg:grid-cols-3 dark:bg-gray-800" @submit.prevent="submit">
          <div ref="staffPickerRef" class="relative">
            <input
              v-model="staffQuery"
              type="text"
              placeholder="搜索并选择违规人员（工号/姓名/单位）"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              @focus="showStaffPanel = true"
              @input="onStaffInput"
            />

            <div
              v-if="showStaffPanel"
              class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
            >
              <button
                v-for="item in filteredStaffOptions"
                :key="item.staff_no"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                @mousedown.prevent="selectStaff(item)"
              >
                {{ item.staff_no }} - {{ item.name }} - {{ item.unit_name ?? '-' }}
              </button>
              <div v-if="filteredStaffOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                没有匹配人员
              </div>
            </div>
          </div>

          <select v-model="form.violation_type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            <option :value="null">选择违规类型</option>
            <option v-for="(label, key) in props.violationTypeOptions" :key="key" :value="Number(key)">
              {{ key }}. {{ label }}
            </option>
          </select>

          <input v-model="form.violation_at" type="datetime-local" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />

          <input v-model="form.deduction_points" type="number" min="0.01" max="25" step="0.01" placeholder="扣分" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />

          <textarea v-model="form.notes" rows="2" placeholder="备注" class="rounded-md border border-gray-300 px-3 py-2 text-sm md:col-span-2 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />

          <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            {{ form.processing ? '提交中...' : '保存记录' }}
          </button>

          <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" @click="applyStaffFilter">
            查看该人员扣分情况
          </button>

          <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" @click="clearStaffFilter">
            查看全部记录
          </button>
        </form>

        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300">
          <p v-for="(value, key) in form.errors" :key="key">{{ value }}</p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
            人员年度扣分汇总（每人满分25）
          </div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">工号</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">姓名</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">单位</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">剩余</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.staffSummaries.length === 0">
                <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">暂无汇总数据</td>
              </tr>
              <tr v-for="item in props.staffSummaries" :key="item.staff_no" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700" @click="pickStaffSummary(item)">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.staff_no }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.staff_name }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.staff_unit_name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.total_deduction }}</td>
                <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400">{{ item.remaining_score }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                  <Link
                    v-if="item.profile_url"
                    :href="item.profile_url"
                    class="text-blue-600 hover:underline dark:text-blue-400"
                    @click.stop
                  >
                    查看历年记录
                  </Link>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
            违规记录明细
          </div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">违规人员</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">违规类型</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">违规时间</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">登记人</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.records.data.length === 0">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无记录</td>
              </tr>
              <tr v-for="item in props.records.data" :key="item.id">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.staff_no }} - {{ item.staff_name }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ typeLabel(item.violation_type) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ formatDate(item.violation_at) }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">-{{ item.deduction_points }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.recorder?.name ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'

type ProfileOption = {
  staff_no: string
  name?: string
  unit_name?: string
}

type ViolationRecord = {
  id: number
  staff_no: string
  staff_name: string
  staff_unit_name?: string | null
  violation_type: number
  violation_at: string
  deduction_points: string | number
  profile?: { user?: { name?: string } }
  recorder?: { name?: string }
}

type StaffSummary = {
  staff_no: string
  staff_name: string
  staff_unit_name?: string | null
  violation_count: number
  total_deduction: number
  remaining_score: number
  profile_url?: string
}

const props = defineProps<{
  records: { data: ViolationRecord[] }
  staffOptions: ProfileOption[]
  staffOptionsCount: number
  staffOptionsError?: string | null
  year: number
  selectedStaffNo?: string | null
  selectedStaffSummary?: StaffSummary | null
  staffSummaries: StaffSummary[]
  violationTypeOptions: Record<string, string>
}>()

const staffQuery = ref('')
const showStaffPanel = ref(false)
const staffPickerRef = ref<HTMLElement | null>(null)

const filteredStaffOptions = computed(() => {
  const keyword = staffQuery.value.trim().toLowerCase()

  if (!keyword) {
    return props.staffOptions
  }

  return props.staffOptions.filter((item) => {
    const haystack = `${item.staff_no} ${item.name ?? ''} ${item.unit_name ?? ''}`.toLowerCase()
    return haystack.includes(keyword)
  })
})

const selectedSummary = computed(() => props.selectedStaffSummary ?? null)

const form = useForm({
  staff_no: null as string | null,
  staff_name: '',
  staff_unit_name: '',
  violation_type: null as number | null,
  violation_at: new Date().toISOString().slice(0, 16),
  deduction_points: 1,
  notes: '',
})

const submit = (): void => {
  if (!form.staff_no && staffQuery.value.trim()) {
    const exactMatch = props.staffOptions.find((item) => {
      const keyword = staffQuery.value.trim().toLowerCase()
      return item.staff_no.toLowerCase() === keyword || (item.name ?? '').toLowerCase() === keyword
    })

    if (exactMatch) {
      selectStaff(exactMatch)
    }
  }

  if (!form.staff_no) {
    form.setError('staff_no', '请先通过搜索选择违规人员。')
    return
  }

  form.clearErrors('staff_no')

  form.post('/ethics/political-violations', {
    preserveScroll: true,
    onSuccess: () => {
      staffQuery.value = ''
      showStaffPanel.value = false
    },
  })
}

const applyStaffFilter = (): void => {
  if (!form.staff_no) {
    form.setError('staff_no', '请先通过搜索选择违规人员。')
    return
  }

  router.get('/ethics/political-violations', { staff_no: form.staff_no }, { preserveState: true })
}

const clearStaffFilter = (): void => {
  router.get('/ethics/political-violations', {}, { preserveState: false })
}

const pickStaffSummary = (item: StaffSummary): void => {
  form.staff_no = item.staff_no
  form.staff_name = item.staff_name
  form.staff_unit_name = item.staff_unit_name ?? ''
  staffQuery.value = `${item.staff_no} - ${item.staff_name}`
  router.get('/ethics/political-violations', { staff_no: item.staff_no }, { preserveState: true })
}

const selectStaff = (item: ProfileOption): void => {
  form.staff_no = item.staff_no
  form.staff_name = item.name ?? ''
  form.staff_unit_name = item.unit_name ?? ''
  staffQuery.value = `${item.staff_no} - ${item.name ?? ''}`
  showStaffPanel.value = false
}

const onStaffInput = (): void => {
  showStaffPanel.value = true
  form.staff_no = null
  form.staff_name = ''
  form.staff_unit_name = ''
}

const closeStaffPanelOnOutsideClick = (event: MouseEvent): void => {
  const target = event.target as HTMLElement | null

  if (!target) {
    return
  }

  if (staffPickerRef.value && !staffPickerRef.value.contains(target)) {
    showStaffPanel.value = false
  }
}

onMounted(() => {
  window.addEventListener('click', closeStaffPanelOnOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', closeStaffPanelOnOutsideClick)
})

const typeLabel = (type: number): string => {
  return props.violationTypeOptions[String(type)] ?? `类型${type}`
}

const formatDate = (value: string): string => {
  return new Date(value).toLocaleString('zh-CN')
}
</script>

