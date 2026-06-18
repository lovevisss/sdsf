<template>
    <Head title="师德师风治理中心" />
    <AppLayout>
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1
                            class="text-3xl font-bold text-gray-900 dark:text-white"
                        >
                            师德师风治理中心
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            五维度年度评分、三级预警和师德档案闭环管理。
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            href="/ethics/profiles"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >一人一档</Link
                        >
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <MetricCard
                        label="档案人数"
                        :value="props.stats.profileCount"
                    />
                    <MetricCard
                        label="蓝色预警"
                        :value="props.stats.blueWarningPersonCount"
                        tone="blue"
                        @click="toggleWarningList('blue')"
                    />
                    <MetricCard
                        label="黄色预警"
                        :value="props.stats.yellowWarningPersonCount"
                        tone="yellow"
                        @click="toggleWarningList('yellow')"
                    />
                    <MetricCard
                        label="红色预警"
                        :value="props.stats.redWarningPersonCount"
                        tone="red"
                        @click="toggleWarningList('red')"
                    />
                    <MetricCard
                        label="未关闭预警"
                        :value="props.stats.openWarningCount"
                    />
                </div>

                <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                年度五维度概览
                            </h2>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                年度：{{ props.stats.year }}；单维满分 20，总分
                                100。
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a
                                :href="`/ethics/reports/export?type=profile_details&year=${props.stats.year}`"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >档案明细 CSV</a
                            >
                            <a
                                :href="`/ethics/reports/export?type=department_summary&year=${props.stats.year}`"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >部门汇总 CSV</a
                            >
                            <a
                                :href="`/ethics/reports/export?type=warning_details&year=${props.stats.year}`"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >预警明细 CSV</a
                            >
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                        <div
                            v-for="(label, key) in props.dimensions"
                            :key="key"
                            class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ label }}
                            </p>
                            <p
                                class="mt-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                记录数：{{ violationCount(String(key)) }}
                            </p>
                            <p
                                v-if="props.stats.selectedSummary"
                                class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                            >
                                当前人员剩余：{{
                                    props.stats.selectedSummary.modules[
                                        String(key)
                                    ] ?? 20
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="activeWarningList"
                    class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        {{ warningTitle(activeWarningList) }}
                    </h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-if="selectedWarningPeople.length === 0"
                            class="text-sm text-gray-500 dark:text-gray-400"
                        >
                            暂无人员
                        </div>
                        <div
                            v-for="item in selectedWarningPeople"
                            :key="`${activeWarningList}-${item.staff_no}`"
                            class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <p
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ item.staff_no }} -
                                    {{ item.name ?? '未绑定用户' }}
                                </p>
                                <span
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                    >总分 {{ item.total_score }}</span
                                >
                            </div>
                            <p
                                class="mt-1 text-sm text-gray-600 dark:text-gray-300"
                            >
                                年度封顶扣分：{{ item.annual_deduction }}
                            </p>
                            <Link
                                v-if="item.profile_url"
                                :href="item.profile_url"
                                class="mt-1 inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
                                >查看档案</Link
                            >
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div
                        class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            快速入口
                        </h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                href="/ethics/political-violations"
                                class="entry-link"
                                >政治素养</Link
                            >
                            <Link
                                href="/ethics/education-violations"
                                class="entry-link"
                                >教育教学</Link
                            >
                            <Link
                                href="/ethics/academic-violations"
                                class="entry-link"
                                >学术诚信</Link
                            >
                            <Link
                                href="/ethics/professional-violations"
                                class="entry-link"
                                >为人师表</Link
                            >
                            <Link
                                href="/ethics/discipline-violations"
                                class="entry-link"
                                >工作纪律</Link
                            >
                        </div>
                    </div>

                    <div
                        class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            最近预警
                        </h2>
                        <div class="mt-4 space-y-3">
                            <div
                                v-if="props.recentWarnings.length === 0"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                暂无预警记录
                            </div>
                            <div
                                v-for="item in props.recentWarnings"
                                :key="item.id"
                                class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <p
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{
                                            item.profile?.user?.name ??
                                            '未绑定用户'
                                        }}
                                    </p>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                        >{{
                                            formatDate(item.detected_at)
                                        }}</span
                                    >
                                </div>
                                <p
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ item.warning_level }} /
                                    {{ item.source_type }} / {{ item.status }}
                                </p>
                                <p
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ item.reason }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref } from 'vue';

type WarningLevel = 'blue' | 'yellow' | 'red';

type ScoreSummary = {
    modules: Record<string, number>;
    totalScore: number;
    totalDeduction: number;
    warningLevel?: string | null;
};

type WarningItem = {
    id: number | string;
    detected_at?: string | null;
    warning_level?: string;
    source_type?: string;
    status?: string;
    reason?: string;
    profile?: { user?: { name?: string } };
};

type AutoWarningPerson = {
    staff_no?: string | null;
    name?: string | null;
    warning_level?: string | null;
    detected_at?: string | null;
    annual_deduction?: number | null;
    total_score?: number | null;
    profile_url?: string | null;
};

const props = defineProps<{
    dimensions: Record<string, string>;
    stats: {
        year: number;
        selectedStaffNo?: string | null;
        profileCount: number;
        openWarningCount: number;
        politicalViolationCount: number;
        educationViolationCount: number;
        academicViolationCount: number;
        professionalViolationCount: number;
        disciplineViolationCount: number;
        selectedSummary?: ScoreSummary | null;
        redWarningPersonCount: number;
        yellowWarningPersonCount: number;
        blueWarningPersonCount: number;
    };
    autoWarningPeople: Record<WarningLevel, AutoWarningPerson[]>;
    recentWarnings: WarningItem[];
}>();

const activeWarningList = ref<WarningLevel | null>(null);

const MetricCard = defineComponent({
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], required: true },
        tone: { type: String, default: 'default' },
    },
    emits: ['click'],
    setup(cardProps, { emit }) {
        return () =>
            h(
                'button',
                {
                    type: 'button',
                    class: [
                        'rounded-lg bg-white p-5 text-left shadow transition hover:ring-2 dark:bg-gray-800',
                        cardProps.tone === 'blue' ? 'hover:ring-blue-300' : '',
                        cardProps.tone === 'yellow'
                            ? 'hover:ring-yellow-300'
                            : '',
                        cardProps.tone === 'red' ? 'hover:ring-red-300' : '',
                    ],
                    onClick: () => emit('click'),
                },
                [
                    h(
                        'p',
                        { class: 'text-sm text-gray-500 dark:text-gray-400' },
                        cardProps.label,
                    ),
                    h(
                        'p',
                        {
                            class: 'mt-2 text-2xl font-semibold text-gray-900 dark:text-white',
                        },
                        String(cardProps.value),
                    ),
                ],
            );
    },
});

const toggleWarningList = (level: WarningLevel): void => {
    activeWarningList.value = activeWarningList.value === level ? null : level;
};

const selectedWarningPeople = computed(() => {
    if (activeWarningList.value === null) {
        return [];
    }

    return props.autoWarningPeople[activeWarningList.value] ?? [];
});

const warningTitle = (level: WarningLevel): string => {
    return {
        blue: '蓝色预警人员',
        yellow: '黄色预警人员',
        red: '红色预警人员',
    }[level];
};

const violationCount = (key: string): number => {
    return (
        {
            political: props.stats.politicalViolationCount,
            education: props.stats.educationViolationCount,
            academic: props.stats.academicViolationCount,
            professional: props.stats.professionalViolationCount,
            discipline: props.stats.disciplineViolationCount,
        }[key] ?? 0
    );
};

const formatDate = (value: unknown): string => {
    if (!value) {
        return '-';
    }

    return new Date(value as string | number | Date).toLocaleString('zh-CN');
};
</script>

<style scoped>
.entry-link {
    border: 1px solid rgb(209 213 219);
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: rgb(55 65 81);
}
</style>
