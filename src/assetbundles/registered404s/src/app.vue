<template lang="html">
    <div>
        <vue-good-table
            mode="remote"
            @on-selected-rows-change="onSelectionChanged"
            @on-page-change="onPageChange"
            @on-sort-change="onSortChange"
            @on-column-filter="onColumnFilter"
            @on-per-page-change="onPerPageChange"
            @on-search="onSearch"
            styleClass="vgt-table condensed"
            :select-options="{ enabled: true, selectionText: 'redirects selected', }"
            :totalRows="totalRecords"
            :pagination-options="{
    enabled: true,
  }"

            :sort-options="{
    enabled: true
  }"
            :rows="rows"
            :columns="columns"
            :search-options="{
    enabled: true,
    trigger: 'enter',
    placeholder: 'Search for a URI',
  }"
        >
            <div slot="selected-row-actions">
                <button class="btn small" v-on:click="actionDelete">Delete</button>
                <button class="btn small" v-on:click="actionIgnore">Ignore</button>
                <button class="btn small" v-on:click="actionUnIgnore">Un-ignore</button>
            </div>
            <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'createRedirect'">
                <button class="btn small" v-on:click="actionCreateRedirect($event, props.row)">Create Redirect</button>
                </span>
                <span v-else>
                {{props.formattedRow[props.column.field]}}
                </span>
            </template>
        </vue-good-table>
    </div>
</template>

<script>
    import Vue from "vue";
    import VueGoodTablePlugin from "vue-good-table"
    import 'vue-good-table/dist/vue-good-table.css'
    import registered404s from "./api/registered404s"

    Vue.use(VueGoodTablePlugin);

    export default Vue.extend({
        data() {
            return {
                selectedItems: [],
                serverParams: {
                    // a map of column filters example: {name: 'john', age: '20'}
                    columnFilters: {},
                    sort: [
                        {
                            field: 'dateUpdated',
                            type: 'asc'
                        }
                    ],

                    page: 1, // what page I want to show
                    perPage: 10 // how many items I'm showing per page
                },
                columns: [
                    {
                        label: 'Site Name',
                        field: 'siteName',
                        sortable: false,
                        hidden: !this.showSiteName,
                    },
                    {
                        label: 'URI',
                        field: 'uri',
                    },
                    {
                        label: 'Hits',
                        field: 'hitCount',
                        type: 'number',
                    },
                    {
                        label: 'First Hit',
                        field: 'dateCreated',
                        type: 'date',
                        dateInputFormat: 'YYYY-MM-DD',
                        dateOutputFormat: 'MMM Do YYYY',
                    },
                    {
                        label: 'Last Hit',
                        field: 'dateUpdated',
                        type: 'date',
                        thClass: 'collapse',
                        dateInputFormat: 'YYYY-MM-DD',
                        dateOutputFormat: 'MMM Do YYYY',
                    },
                    {
                        label: 'Ignored',
                        field: 'ignored',
                        type: 'boolean',
                        formatFn: this.formatBool,
                        thClass: 'collapse',
                        filterOptions: {
                            enabled: true,
                            placeholder: 'All',
                            filterValue: false,
                            filterDropdownItems: [
                                {value: true, text: 'Only Ignored'},
                                {value: false, text: 'Only Un-ignored'},
                            ]
                        },
                    },
                    {
                        label: '',
                        field: 'createRedirect',
                        tdClass: 'button',
                        sortable: false
                    }
                ],
                rows: [],
                totalRecords: 0
            };
        },
        methods: {
            updateParams(newProps) {
                this.serverParams = Object.assign({}, this.serverParams, newProps);
            },

            onPageChange(params) {
                this.updateParams({page: params.currentPage});
                this.loadItems();
            },

            onPerPageChange(params) {
                this.updateParams({perPage: params.currentPerPage});
                this.loadItems();
            },
            onSelectionChanged(params) {
                this.selectedItems = params.selectedRows
            },

            onSortChange(params) {
                this.updateParams({
                    sort: {
                        type: params[0].type,
                        field: params[0].field,
                    },
                });
                this.loadItems();
            },

            onColumnFilter(params) {
                this.updateParams(params);
                this.loadItems();
            },

            onSearch(params) {
                this.updateParams(params);
                this.loadItems();
            },

            // load items is what brings back the rows from server
            loadItems() {
                registered404s.get404s(this.serverParams).then(response => {
                    this.totalRecords = response.data.totalRecords;
                    this.rows = response.data.rows;
                });
            },
            actionDelete() {
                registered404s.delete404s(this.selectedItems).then(() => {
                    this.loadItems();
                });

            },
            actionIgnore() {
                registered404s.ignore404s(this.selectedItems).then(() => {
                    this.loadItems();
                });
            },
            actionUnIgnore() {
                registered404s.unIgnore404s(this.selectedItems).then(() => {
                    this.loadItems();
                });
            },

            actionCreateRedirect(event, row) {
                event.preventDefault()
                event.stopPropagation()
                window.location = row.createUrl;
            },

            formatBool(val) {
                if (val == 0) {
                    return 'No'
                }
                return 'Yes'
            },
            showSiteName() {
                return Craft.sites.length > 1;
            }
        },
        beforeMount() {
            this.loadItems();
        }
    });
</script>

<style lang="scss" scoped>

</style>
