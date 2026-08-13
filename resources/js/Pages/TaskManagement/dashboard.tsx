import { ModuleRoadmap, type RoadmapPhase } from '@/components/module-roadmap';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Task Management', href: '/tasks' }];

const phases: RoadmapPhase[] = [
    {
        phase: '2',
        title: 'Companies, projects and tasks',
        summary: 'Work companies, projects and the task board, with direct assignment, open tasks and employee acceptance.',
    },
    {
        phase: '3',
        title: 'Time, capacity and review',
        summary: 'Employee availability, workload against capacity, the work timer, timesheet submission and approval, and creative review rounds.',
    },
];

export default function TaskManagementDashboard() {
    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Task Management" />
            <ModuleRoadmap
                heading="Internal Task Management"
                description="Owns every tm_ table. This module has no dependency on CRM — it never reads leads, pipelines or campaigns."
                phases={phases}
            />
        </TaskLayout>
    );
}
