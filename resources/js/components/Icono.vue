<script setup lang="ts">
import {
    ArrowRight, Award, BookOpen, Briefcase, Building2, Calendar, CalendarDays, ChartColumn, CircleHelp, Contact, Download, Eye,
    File, FileText, FlaskConical, Folder, GraduationCap, Handshake, History, House, Image, Images, Inbox, LayoutDashboard,
    Library, Link, Mail, Menu, Network, Newspaper, Settings, Shield, Star, Tags, UserCheck, Users, Video,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';

const props = defineProps<{ nombre?: string | null }>();

const iconos: Record<string, Component> = {
    'layout-dashboard': LayoutDashboard,
    users: Users,
    shield: Shield,
    history: History,
    menu: Menu,
    home: House,
    image: Image,
    star: Star,
    link: Link,
    settings: Settings,
    'file-text': FileText,
    'graduation-cap': GraduationCap,
    network: Network,
    calendar: Calendar,
    'help-circle': CircleHelp,
    library: Library,
    folder: Folder,
    'flask-conical': FlaskConical,
    'book-open': BookOpen,
    handshake: Handshake,
    briefcase: Briefcase,
    'user-check': UserCheck,
    newspaper: Newspaper,
    tags: Tags,
    'calendar-days': CalendarDays,
    images: Images,
    video: Video,
    mail: Mail,
    inbox: Inbox,
    'bar-chart-3': ChartColumn,
    contact: Contact,
    eye: Eye,
    download: Download,
    award: Award,
    building: Building2,
    'arrow-right': ArrowRight,
};

const componente = computed(() => (props.nombre && iconos[props.nombre]) || File);
</script>

<template>
    <component :is="componente" />
</template>
