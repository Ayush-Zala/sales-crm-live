import { useSelectionStore } from "@/store/selection-store";
import { hasRole } from "@/utils/AccessManager";
import {
    CustomPaging,
    FilteringState,
    IntegratedFiltering,
    IntegratedSelection,
    PagingState,
    SearchState,
    SelectionState,
} from "@devexpress/dx-react-grid";
import {
    Grid,
    PagingPanel,
    SearchPanel,
    Table,
    TableFilterRow,
    TableFixedColumns,
    TableHeaderRow,
    TableSelection,
    Toolbar,
    VirtualTable,
} from "@devexpress/dx-react-grid-material-ui";
import { useForm } from "@inertiajs/react";
import { Paper } from "@mui/material";
import { concat, filter, lowerCase, map } from "lodash";
import debounce from "lodash.debounce";
import { useCallback, useEffect, useState } from "react";
import { MuiTable } from "./mui-table";
import { NoDataCell } from "./no-data-cell";
import { Loading } from "./loading";

export default function SelectPaginatedDataTable({
    loading,
    columns,
    row,
    url,
    currentPage,
    perPage,
    total,
    tableCellComponent,
    columnsExtension,
    auth,
}) {
    const [searchValue, setSearchValue] = useState("");
    const [initialPageLoad, setInitialPageLoad] = useState(true);

    const role = hasRole(auth, ["Admin", "Business Development Manager"]);

    // const [leftColumns] = useState([TableSelection.COLUMN_TYPE]);
    const tempLeftColumns = map(
        filter(columns, (column) => lowerCase(column.title) === "id"),
        "name"
    );

    const leftColumns = concat(tempLeftColumns, TableSelection.COLUMN_TYPE);

    const rightColumns = map(
        filter(
            columns,
            (column) =>
                lowerCase(column.title) === "actions" ||
                lowerCase(column.title) === "disposition"
        ),
        "name"
    );

    const selection = useSelectionStore((state) => state.selection);
    const setSelection = useSelectionStore((state) => state.setSelection);

    // const [selection, setSelection] = useState([]);

    const { data, setData, get } = useForm({
        preserveState: true,
        preserveScroll: true,
        page: currentPage,
        per_page: perPage,
        search: searchValue,
    });

    const debouncedGet = useCallback(
        debounce(() => {
            get(url, {
                preserveState: true,
                preserveScroll: true,
                page: data.page,
                per_page: data.per_page,
                search: searchValue,
            });
        }, 100),
        [data.page, data.per_page, searchValue]
    );

    useEffect(() => {
        if (initialPageLoad) {
            setInitialPageLoad(false);
            return;
        } else {
            debouncedGet();
        }
    }, [data.page, data.per_page, debouncedGet]);

    const onPageChange = useCallback(
        (current_page) => {
            setData((prevData) => ({
                ...prevData,
                page: current_page + 1,
            }));
        },
        [setData]
    );

    const onSearchChange = useCallback(
        (search) => {
            setSearchValue(search);
            setData((prevData) => ({
                ...prevData,
                search: search,
                page: 1,
            }));
        },
        [setData]
    );

    const onPageSizeChange = useCallback(
        (pageSize) => {
            setData((prevData) => ({
                ...prevData,
                per_page: pageSize,
                page: 1,
            }));
        },
        [setData]
    );

    return (
        <div className="container">
            {role && <span>Total rows selected: {selection.length}</span>}
            <Paper>
                <Loading loading={loading} />
                <Grid
                    rows={row}
                    columns={columns}
                    direction="row"
                    justify="center"
                    spacing={2}
                >
                    <SearchState onValueChange={onSearchChange} />
                    <VirtualTable />
                    <PagingState
                        currentPage={currentPage - 1}
                        onCurrentPageChange={onPageChange}
                        pageSize={perPage}
                        onPageSizeChange={onPageSizeChange}
                    />
                    {role && (
                        <SelectionState
                            selection={selection}
                            onSelectionChange={setSelection}
                        />
                    )}

                    <FilteringState defaultFilters={[]} />
                    {role && <IntegratedSelection />}
                    <IntegratedFiltering />
                    <Table
                        cellComponent={tableCellComponent}
                        columnExtensions={columnsExtension}
                        tableComponent={({ forwardedRef, ...props }) => (
                            <MuiTable ref={forwardedRef} {...props} />
                        )}
                        noDataCellComponent={(props) => (
                            <NoDataCell {...props} loading={loading} />
                        )}
                    />

                    <TableHeaderRow />
                    <TableFilterRow />
                    {role && <TableSelection showSelectAll />}
                    <TableFixedColumns
                        leftColumns={leftColumns}
                        rightColumns={rightColumns}
                    />
                    <CustomPaging totalCount={total} />
                    <Toolbar />
                    <SearchPanel />
                    <PagingPanel pageSizes={[10, 25, 50, 100]} />
                </Grid>
            </Paper>
        </div>
    );
}
