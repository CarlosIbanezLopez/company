<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, Globe } from '@lucide/vue';
import { computed } from 'vue';
import Icono from '@/components/Icono.vue';
import NavUser from '@/components/NavUser.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { u } from '@/lib/sitio';
import type { GrupoMenuAdmin } from '@/types';

/** Menú lateral dinámico: se carga desde la tabla menu_items según los permisos del rol. */
const page = usePage();
const menu = computed(() => (page.props.menuAdmin ?? []) as GrupoMenuAdmin[]);

const rutaActual = computed(() => page.url.split('?')[0]);

function activa(url: string | null): boolean {
    if (!url) return false;
    const ruta = new URL(url, window.location.origin).pathname;
    return rutaActual.value === ruta || (!ruta.endsWith('/admin') && rutaActual.value.startsWith(`${ruta}/`));
}

const grupoActivo = (g: GrupoMenuAdmin) => g.items.some((i) => activa(i.url));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="u('/admin')">
                            <span class="relative flex size-8 shrink-0 items-center justify-center bg-sidebar-primary text-xs font-black text-sidebar-primary-foreground">AE<span class="absolute inset-x-0 bottom-0 h-0.5 bg-marca" /></span>
                            <span class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">Administración</span>
                                <span class="truncate text-xs text-muted-foreground">Panel de la Carrera</span>
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <template v-for="grupo in menu" :key="grupo.titulo">
                        <SidebarMenuItem v-if="grupo.url && !grupo.items.length">
                            <SidebarMenuButton as-child :is-active="activa(grupo.url)" :tooltip="grupo.titulo">
                                <Link :href="grupo.url"><Icono :nombre="grupo.icono" /><span>{{ grupo.titulo }}</span></Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <Collapsible v-else as-child :default-open="grupoActivo(grupo)" class="group/collapsible">
                            <SidebarMenuItem>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuButton :tooltip="grupo.titulo">
                                        <Icono :nombre="grupo.icono" />
                                        <span>{{ grupo.titulo }}</span>
                                        <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="item in grupo.items" :key="item.titulo">
                                            <SidebarMenuSubButton as-child :is-active="activa(item.url)">
                                                <Link :href="item.url!"><span>{{ item.titulo }}</span></Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </template>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="Ver sitio público">
                        <a :href="u('/')" target="_blank"><Globe /><span>Ver sitio público</span></a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
