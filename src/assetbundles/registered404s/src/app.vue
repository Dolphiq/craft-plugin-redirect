<template lang="html">
    <div>
        <vue-good-table
            mode="remote"
            @on-page-change="onPageChange"
            @on-sort-change="onSortChange"
            @on-column-filter="onColumnFilter"
            @on-per-page-change="onPerPageChange"
            :totalRows="totalRecords"
            pagination-options="{
    enabled: true,
  }"
            :sort-options="{
    enabled: true,
    initialSortBy: {field: 'firstHit', type: 'asc'}
  }"
            :rows="rows"
            :columns="columns"/>
    </div>
</template>

<script>
    import Vue from "vue";
    import VueGoodTablePlugin from "vue-good-table"
    import 'vue-good-table/dist/vue-good-table.css'
    import registered404s from "./api/registered404s"

    Vue.use(VueGoodTablePlugin);

    export default Vue.extend({
        data(){
            return {
                serverParams: {
                    // a map of column filters example: {name: 'john', age: '20'}
                    columnFilters: {
                    },
                    sort: [
                    ],

                    page: 1, // what page I want to show
                    perPage: 10 // how many items I'm showing per page
                },
                columns: [
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
                        dateInputFormat: 'YYYY-MM-DD',
                        dateOutputFormat: 'MMM Do YYYY',
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

            onSortChange(params) {
                this.updateParams({
                    sort: {
                        type: params.sortType,
                        field: this.columns[params.columnIndex].field,
                    },
                });
                this.loadItems();
            },

            onColumnFilter(params) {
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
        },
        beforeMount() {
            this.loadItems();
        }
    });
</script>

<style lang="scss" scoped>
</style>
