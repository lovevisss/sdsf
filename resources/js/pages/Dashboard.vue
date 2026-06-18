<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BarChart3,
    BookOpenCheck,
    BriefcaseBusiness,
    ClipboardList,
    FileDown,
    FolderKanban,
    GraduationCap,
    Landmark,
    Scale,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type WarningLevel = 'blue' | 'yellow' | 'red';

type WarningPerson = {
    staff_no?: string | null;
    name?: string | null;
    unit_name?: string | null;
    annual_deduction?: number | null;
    total_score?: number | null;
    profile_url?: string | null;
};

type DashboardSummary = {
    profileCount: number;
    openWarningCount: number;
    warningLevels: {
        blue: number;
        yellow: number;
        red: number;
    };
    warningPeople: Record<WarningLevel, WarningPerson[]>;
    violations: {
        political: number;
        education: number;
        academic: number;
        professional: number;
        discipline: number;
    };
    totalViolationCount: number;
};

const props = defineProps<{
    summary: DashboardSummary;
}>();

const activeWarningLevel = ref<WarningLevel | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '工作台',
        href: dashboard().url,
    },
];

const statCards = computed(() => [
    {
        label: '师德档案',
        value: props.summary.profileCount,
        suffix: '人',
        helper: '已纳入一人一档管理',
        icon: UsersRound,
        cardClass: 'border-slate-200 bg-slate-50 text-slate-950',
        iconClass: 'text-blue-600',
    },
    {
        label: '未关闭预警',
        value: props.summary.openWarningCount,
        suffix: '条',
        helper: '蓝黄红三级预警合计',
        icon: AlertTriangle,
        cardClass: 'border-rose-200 bg-rose-50 text-rose-700',
        iconClass: 'text-rose-600',
    },
    {
        label: '违规核定记录',
        value: props.summary.totalViolationCount,
        suffix: '条',
        helper: '五个维度年度归集',
        icon: ClipboardList,
        cardClass: 'border-amber-200 bg-amber-50 text-amber-700',
        iconClass: 'text-amber-600',
    },
    {
        label: '评分模型',
        value: 100,
        suffix: '分',
        helper: '五维度各 20 分',
        icon: ShieldCheck,
        cardClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        iconClass: 'text-emerald-600',
    },
]);

const warningCards = computed(() => [
    {
        level: 'blue' as const,
        label: '蓝色预警',
        value: props.summary.warningLevels.blue,
        class: 'border-blue-200 bg-blue-50 text-blue-700',
        activeClass: 'ring-blue-300',
    },
    {
        level: 'yellow' as const,
        label: '黄色预警',
        value: props.summary.warningLevels.yellow,
        class: 'border-amber-200 bg-amber-50 text-amber-700',
        activeClass: 'ring-amber-300',
    },
    {
        level: 'red' as const,
        label: '红色预警',
        value: props.summary.warningLevels.red,
        class: 'border-rose-200 bg-rose-50 text-rose-700',
        activeClass: 'ring-rose-300',
    },
]);

const selectableWarningLevels: WarningLevel[] = ['blue', 'yellow'];

const selectedWarningPeople = computed(() => {
    if (activeWarningLevel.value === null) {
        return [];
    }

    return props.summary.warningPeople[activeWarningLevel.value] ?? [];
});

const activeWarningTitle = computed(() => {
    if (activeWarningLevel.value === null) {
        return '';
    }

    return {
        blue: '蓝色预警人员',
        yellow: '黄色预警人员',
        red: '红色预警人员',
    }[activeWarningLevel.value];
});

const toggleWarningLevel = (level: WarningLevel): void => {
    if (!selectableWarningLevels.includes(level)) {
        return;
    }

    activeWarningLevel.value =
        activeWarningLevel.value === level ? null : level;
};

const dimensionRows = computed(() => [
    {
        name: '政治素养',
        count: props.summary.violations.political,
        icon: Landmark,
        href: '/ethics/political-violations',
    },
    {
        name: '教育教学',
        count: props.summary.violations.education,
        icon: GraduationCap,
        href: '/ethics/education-violations',
    },
    {
        name: '学术诚信',
        count: props.summary.violations.academic,
        icon: BookOpenCheck,
        href: '/ethics/academic-violations',
    },
    {
        name: '为人师表',
        count: props.summary.violations.professional,
        icon: UserRoundCheck,
        href: '/ethics/professional-violations',
    },
    {
        name: '工作纪律',
        count: props.summary.violations.discipline,
        icon: BriefcaseBusiness,
        href: '/ethics/discipline-violations',
    },
]);

const shortcuts = [
    {
        title: '师德治理中心',
        description: '查看年度分布、预警排行和治理概览',
        href: '/ethics/dashboard',
        icon: BarChart3,
    },
    {
        title: '师德档案',
        description: '检索教师档案、五维分值和历年扣分',
        href: '/ethics/profiles',
        icon: FolderKanban,
    },
    {
        title: '投诉处置',
        description: '跟踪案件受理、调查、处置和归档',
        href: '/ethics/cases',
        icon: Scale,
    },
    {
        title: '报表导出',
        description: '导出档案明细、部门汇总、维度统计',
        href: '/ethics/reports/export?type=profiles',
        icon: FileDown,
    },
];
</script>

<template>
    <Head title="工作台" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-slate-100 p-4 text-slate-950 md:p-6">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <section
                    class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm md:px-6"
                >
                    <div
                        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <h1
                                class="text-2xl font-bold tracking-normal text-slate-950"
                            >
                                师德师风监督预警平台
                            </h1>
                            <p class="mt-1 text-sm text-slate-600">
                                聚合档案、核定扣分、三级预警和整改处置的统一工作台
                            </p>
                        </div>

                        <Link
                            href="/ethics/dashboard"
                            class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            进入治理中心
                        </Link>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in statCards"
                        :key="card.label"
                        class="rounded-lg border p-4 shadow-sm"
                        :class="card.cardClass"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-medium">{{
                                card.label
                            }}</span>
                            <component
                                :is="card.icon"
                                class="size-5"
                                :class="card.iconClass"
                            />
                        </div>
                        <div class="mt-3 flex items-end gap-1">
                            <span class="text-3xl leading-none font-bold">{{
                                card.value
                            }}</span>
                            <span class="text-sm font-semibold">{{
                                card.suffix
                            }}</span>
                        </div>
                        <p class="mt-2 text-xs opacity-80">{{ card.helper }}</p>
                    </article>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <h2
                                    class="text-base font-semibold text-slate-950"
                                >
                                    五维违规记录
                                </h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    按平台评分模型归集，作为年度扣分和预警依据
                                </p>
                            </div>
                            <div
                                class="grid grid-cols-3 gap-2 text-center text-xs font-semibold"
                            >
                                <button
                                    v-for="item in warningCards"
                                    :key="item.label"
                                    type="button"
                                    class="rounded-lg border px-3 py-2"
                                    :class="[
                                        item.class,
                                        selectableWarningLevels.includes(
                                            item.level,
                                        )
                                            ? 'cursor-pointer transition hover:ring-2'
                                            : 'cursor-default',
                                        activeWarningLevel === item.level
                                            ? `ring-2 ${item.activeClass}`
                                            : '',
                                    ]"
                                    :disabled="
                                        !selectableWarningLevels.includes(
                                            item.level,
                                        )
                                    "
                                    @click="toggleWarningLevel(item.level)"
                                >
                                    <div>{{ item.value }}</div>
                                    <div class="mt-1">{{ item.label }}</div>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="activeWarningLevel"
                            class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <h3
                                    class="text-sm font-semibold text-slate-950"
                                >
                                    {{ activeWarningTitle }}
                                </h3>
                                <span class="text-xs text-slate-500">
                                    {{ selectedWarningPeople.length }} 人
                                </span>
                            </div>
                            <div class="mt-3 grid gap-2">
                                <div
                                    v-if="selectedWarningPeople.length === 0"
                                    class="rounded-md border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500"
                                >
                                    暂无人员
                                </div>
                                <Link
                                    v-for="person in selectedWarningPeople"
                                    :key="`${activeWarningLevel}-${person.staff_no}`"
                                    :href="person.profile_url ?? '#'"
                                    class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm transition hover:border-slate-300 hover:bg-slate-50"
                                    :class="
                                        person.profile_url
                                            ? ''
                                            : 'pointer-events-none opacity-60'
                                    "
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium text-slate-950"
                                        >
                                            {{ person.staff_no || '-' }} -
                                            {{ person.name || '-' }}
                                        </span>
                                        <span
                                            class="mt-0.5 block truncate text-xs text-slate-500"
                                        >
                                            {{
                                                person.unit_name || '未设置单位'
                                            }}
                                        </span>
                                    </span>
                                    <span
                                        class="shrink-0 text-xs text-slate-500"
                                    >
                                        总分 {{ person.total_score ?? '-' }}
                                    </span>
                                </Link>
                            </div>
                        </div>

                        <div
                            class="mt-5 overflow-hidden rounded-lg border border-slate-200"
                        >
                            <div
                                class="grid grid-cols-12 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700"
                            >
                                <span class="col-span-5">维度</span>
                                <span class="col-span-3">年度满分</span>
                                <span class="col-span-2">记录数</span>
                                <span class="col-span-2 text-right">操作</span>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div
                                    v-for="row in dimensionRows"
                                    :key="row.name"
                                    class="grid grid-cols-12 items-center px-4 py-3 text-sm"
                                >
                                    <div
                                        class="col-span-5 flex items-center gap-3 font-medium text-slate-950"
                                    >
                                        <component
                                            :is="row.icon"
                                            class="size-4 text-slate-600"
                                        />
                                        <span>{{ row.name }}</span>
                                    </div>
                                    <span class="col-span-3 text-slate-600"
                                        >20 分</span
                                    >
                                    <span class="col-span-2 text-slate-950"
                                        >{{ row.count }} 条</span
                                    >
                                    <div class="col-span-2 text-right">
                                        <Link
                                            :href="row.href"
                                            class="inline-flex h-8 items-center justify-center rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            查看
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-base font-semibold text-slate-950">
                            快捷入口
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            常用业务入口集中在这里，减少来回查找
                        </p>

                        <div class="mt-4 grid gap-3">
                            <Link
                                v-for="item in shortcuts"
                                :key="item.title"
                                :href="item.href"
                                class="group rounded-lg border border-slate-200 p-4 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-lg bg-slate-950 p-2 text-white"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4"
                                        />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-semibold text-slate-950"
                                        >
                                            {{ item.title }}
                                        </h3>
                                        <p
                                            class="mt-1 text-sm leading-5 text-slate-600"
                                        >
                                            {{ item.description }}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
