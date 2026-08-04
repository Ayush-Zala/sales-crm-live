import {
    CustomPaging,
    IntegratedFiltering,
    PagingState,
    SearchState,
} from "@devexpress/dx-react-grid";
import {
    Grid,
    PagingPanel,
    SearchPanel,
    Table,
    TableFixedColumns,
    TableHeaderRow,
    Toolbar,
} from "@devexpress/dx-react-grid-material-ui";
import { useForm } from "@inertiajs/react";
import { Paper, TableContainer } from "@mui/material";
import debounce from "lodash.debounce";
import { useCallback, useEffect, useState } from "react";
import { MuiTable } from "@/Components/mui-table";
import { concat, filter, lowerCase, map } from "lodash";
import { NoDataCell } from "./no-data-cell";
import { Loading } from "./loading";

export default function PaginatedTable({
    loading,
    columns,
    row,
    url,
    currentPage,
    perPage,
    total,
    tableCellComponent,
}) {
    const [searchValue, setSearchValue] = useState("");
    const { data, setData, get } = useForm({
        preserveState: true,
        preserveScroll: true,
        page: currentPage,
        per_page: perPage,
        search: searchValue,
    });

    const leftColumns = map(
        filter(columns, (column) => lowerCase(column.title) === "id"),
        "name"
    );

    const rightColumns = map(
        filter(columns, (column) => lowerCase(column.title) === "actions"),
        "name"
    );

    const tableColumnExtensions = concat(
        map(leftColumns, (column) => ({
            columnName: column,
            align: "right",
            width: 10,
        })),
        map(rightColumns, (column) => ({
            columnName: column,
            align: "left",
            width: 10,
        })),
        [
            { columnName: "createdAt", align: "right", width: 10 },
            { columnName: "updatedAt", align: "right", width: 10 },
        ]
    );

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
        debouncedGet();
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
            }));
        },
        [setData]
    );

    return (
        <TableContainer component={Paper} sx={{ position: "relative" }}>
            <Loading loading={loading} />
            <Grid rows={row} columns={columns}>
                <SearchState onValueChange={onSearchChange} />
                <IntegratedFiltering />
                <PagingState
                    currentPage={currentPage - 1}
                    onCurrentPageChange={onPageChange}
                    pageSize={perPage}
                    onPageSizeChange={onPageSizeChange}
                />
                <CustomPaging totalCount={total} />
                <Table
                    cellComponent={tableCellComponent}
                    columnExtensions={tableColumnExtensions}
                    tableComponent={({ forwardedRef, ...props }) => (
                        <MuiTable ref={forwardedRef} {...props} />
                    )}
                    noDataCellComponent={(props) => (
                        <NoDataCell {...props} loading={loading} />
                    )}
                />
                <TableHeaderRow />
                <TableFixedColumns
                    leftColumns={leftColumns}
                    rightColumns={rightColumns}
                />
                <Toolbar />
                <SearchPanel />
                <PagingPanel pageSizes={[10, 25, 50]} />
            </Grid>
        </TableContainer>
    );
}
