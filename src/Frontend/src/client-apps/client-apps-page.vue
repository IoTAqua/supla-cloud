<template>
    <div>
        <div class="container">
            <div class="clearfix left-right-header">
                <div>
                    <h1 v-title>{{ $t('Client\'s Apps') }}</h1>
                    <h4 class="text-muted">{{ $t('smartphones, tablets, etc.') }}</h4>
                </div>
                <div>
                    <devices-registration-button field="clientsRegistrationEnabled"
                        caption="Registration of new clients"></devices-registration-button>
                </div>
            </div>
        </div>
        <div class="container">
            <client-app-filters @filter-function="filterFunction = $event"
                @compare-function="compareFunction = $event"
                @filter="filter()"></client-app-filters>
        </div>
        <square-links-grid v-if="clientApps && filteredClientApps.length"
            :count="filteredClientApps.length"
            class="square-links-height-250">
            <div v-for="app in filteredClientApps"
                :key="app.id"
                :ref="'app-tile-' + app.id">
                <client-app-tile-editable :app="app"
                    @change="filter()"
                    @delete="removeClientFromList(app)"></client-app-tile-editable>
            </div>
        </square-links-grid>
        <empty-list-placeholder v-else-if="clientApps"></empty-list-placeholder>
        <loader-dots v-else></loader-dots>
        <div class="hidden"
            v-if="clientApps">
            <!--allow filtered-out items to still receive status updates-->
            <client-app-connection-status-label :app="app"
                :key="app.id"
                v-for="app in clientApps"></client-app-connection-status-label>
        </div>
    </div>
</template>

<script>
    import LoaderDots from "../common/gui/loaders/loader-dots.vue";
    import DevicesRegistrationButton from "src/devices/list/devices-registration-button.vue";
    import ClientAppConnectionStatusLabel from "./client-app-connection-status-label.vue";
    import EmptyListPlaceholder from "src/common/gui/empty-list-placeholder.vue";
    import ClientAppTileEditable from "./client-app-tile-editable";
    import ClientAppFilters from "./client-app-filters";

    export default {
        components: {
            ClientAppFilters,
            ClientAppTileEditable,
            ClientAppConnectionStatusLabel,
            DevicesRegistrationButton,
            EmptyListPlaceholder,
            LoaderDots,
        },
        data() {
            return {
                clientApps: undefined,
                filteredClientApps: [],
                filterFunction: () => true,
                compareFunction: () => 1,
            };
        },
        mounted() {
            this.$http.get('client-apps?include=accessId')
                .then(({body}) => {
                    this.clientApps = body;
                    this.filter();
                });
        },
        methods: {
            filter() {
                this.filteredClientApps = this.clientApps ? this.clientApps.filter(this.filterFunction) : this.clientApps;
                if (this.filteredClientApps) {
                    this.filteredClientApps = this.filteredClientApps.sort(this.compareFunction);
                }
            },
            removeClientFromList(app) {
                this.clientApps.splice(this.clientApps.indexOf(app), 1);
                this.filter();
            }
        }
    };
</script>
