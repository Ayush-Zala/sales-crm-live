import Paper from "@mui/material/Paper";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import TextField from "@mui/material/TextField";
import Stack from "@mui/material/Stack";

import { Checkbox, TableSortLabel } from "@mui/material";

import useUpdateSearchParam from "@/hooks/use-update-search-params";
import theme from "@/theme";
import { useState, useCallback, useEffect } from "react";
import TablePaginationComponent from "./tableComponents/TablePagination";
import _ from "lodash";
import PageNumberSearch from "@/Pages/Account/PageNumberSearch";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { usePage, router } from "@inertiajs/react";

const SelectPaginatedTable = ({
    columns,
    rows,
    CellComponent,
    current_page,
    per_page,
    total,
    last_page,
    url,
    orderByFilter,
    sortFilter,
    selection = [],
    setSelection = () => {},
    hasAssignPermission = false,
}) => {
    const [currentPageNumber, setCurrentPageNumber] = useState(current_page);
    // State for sorting
    const [order, setOrder] = useState(sortFilter || "asc");
    const [orderBy, setOrderBy] = useState(orderByFilter || "");

    const handleSelectAllClick = (event) => {
        if (event.target.checked) {
            const newSelected = rows.map((n) => n.id);
            setSelection(newSelected);

            return;
        }

        setSelection([]);
    };

    const handleClick = (event, id) => {
        const selectedIndex = selection.indexOf(id);
        let newSelected = [];

        if (selectedIndex === -1) {
            newSelected = newSelected.concat(selection, id);
        } else if (selectedIndex === 0) {
            newSelected = newSelected.concat(selection.slice(1));
        } else if (selectedIndex === selection.length - 1) {
            newSelected = newSelected.concat(selection.slice(0, -1));
        } else if (selectedIndex > 0) {
            newSelected = newSelected.concat(
                selection.slice(0, selectedIndex),
                selection.slice(selectedIndex + 1)
            );
        }
        setSelection(newSelected);
    };

    // Handle sorting for "dispositions" column
    const handleSort = (columnId) => {
        if (columnId === "dispositionDate") {
            const isAsc = orderBy === columnId && order === "asc";
            setOrder(isAsc ? "desc" : "asc");
            setOrderBy(columnId);

            // Update the search parameter only for "dispositions"
            useUpdateSearchParam(
                {
                    sort: isAsc ? "desc" : "asc",
                    order: columnId,
                    page: 1,
                    per_page: 50,
                },
                url
            );
        }
    };

    return (
        <Paper sx={{ width: "100%", overflow: "hidden" }}>
            <TableContainer sx={{ maxHeight: `calc(100vh - 340px)` }}>
                <Table stickyHeader size="small">
                    <TableHead>
                        <TableRow>
                            {hasAssignPermission && (
                                <TableCell padding="checkbox">
                                    <Checkbox
                                        color="primary"
                                        indeterminate={
                                            selection.length > 0 &&
                                            selection.length < rows.length
                                        }
                                        checked={
                                            rows.length > 0 &&
                                            selection.length === rows.length
                                        }
                                        onChange={handleSelectAllClick}
                                    />
                                </TableCell>
                            )}
                            {columns.map((column, index) => (
                                <TableCell
                                    key={index}
                                    align={column.align}
                                    sx={
                                        column.align === "right"
                                            ? {
                                                  position: "sticky",
                                                  right: 0,
                                                  backgroundColor: (theme) =>
                                                      theme.palette.background
                                                          .paper,
                                                  whiteSpace: "nowrap",
                                                  borderLeft: "1px solid",
                                                  borderColor: (theme) =>
                                                      theme.palette.divider,
                                              }
                                            : { whiteSpace: "nowrap" }
                                    }
                                >
                                    {/* {column.id === "dispositionDate" ? (
                                        <TableSortLabel
                                            active={orderBy === column.id}
                                            direction={
                                                orderBy === column.id
                                                    ? order
                                                    : "asc"
                                            }
                                            onClick={() =>
                                                handleSort(column.id)
                                            }
                                        >
                                            {column.label}
                                        </TableSortLabel>
                                    ) : ( */}
                                    {column.label}
                                    {/* )} */}
                                </TableCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            {hasAssignPermission && <TableCell />}
                            {columns.map((column, index) => (
                                <TableCell
                                    key={index}
                                    align={column.align}
                                    sx={
                                        column.align === "right"
                                            ? {
                                                  position: "sticky",
                                                  right: 0,
                                                  backgroundColor: (theme) =>
                                                      theme.palette.background
                                                          .paper,
                                                  whiteSpace: "nowrap",
                                                  borderLeft: "1px solid",
                                                  borderColor: (theme) =>
                                                      theme.palette.divider,
                                                  zIndex:
                                                      theme.zIndex.appBar + 1,
                                              }
                                            : { whiteSpace: "nowrap" }
                                    }
                                >
                                    <SearchFromTableCell
                                        column={column}
                                        url={url}
                                    />
                                </TableCell>
                            ))}
                        </TableRow>
                        {rows.map((row, index) => {
                            const isItemSelected = selection.includes(row.id);
                            const labelId = `enhanced-table-checkbox-${index}`;

                            return (
                                <TableRow key={index}>
                                    {hasAssignPermission && (
                                        <TableCell padding="checkbox">
                                            <Checkbox
                                                tabIndex={index + 1}
                                                color="primary"
                                                checked={isItemSelected}
                                                inputProps={{
                                                    "aria-labelledby": labelId,
                                                }}
                                                onClick={(event) =>
                                                    handleClick(event, row.id)
                                                }
                                            />
                                        </TableCell>
                                    )}
                                    {columns.map((column, index) => (
                                        <TableCell
                                            key={index}
                                            align={column.align}
                                            sx={
                                                column.align === "right"
                                                    ? {
                                                          position: "sticky",
                                                          right: 0,
                                                          backgroundColor: (
                                                              theme
                                                          ) =>
                                                              theme.palette
                                                                  .background
                                                                  .paper,

                                                          whiteSpace: "nowrap",
                                                          borderLeft:
                                                              "1px solid",
                                                          borderColor: (
                                                              theme
                                                          ) =>
                                                              theme.palette
                                                                  .divider,
                                                      }
                                                    : { whiteSpace: "nowrap" }
                                            }
                                        >
                                            {/* Custom Cell Rendering */}
                                            <CellComponent
                                                row={row}
                                                column={column}
                                            />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </TableContainer>

            <Stack
                direction="row"
                justifyContent="flex-end"
                alignItems="center"
                spacing={2}
                sx={{ padding: 2 }}
            >
                <TablePaginationComponent
                    current_page={current_page}
                    per_page={per_page}
                    total={total}
                    url={url}
                />
                <PageNumberSearch
                    current_page={current_page}
                    url={url}
                    last_page={last_page}
                />
            </Stack>
        </Paper>
    );
};

export default SelectPaginatedTable;

const SearchFromTableCell = ({ column }) => {
    const { url } = usePage();
    const searchParams = new URLSearchParams(window.location.search);

    // Preserve all existing query params
    const existingParams = Object.fromEntries(searchParams.entries());

    // Get columnFilters from URL
    const filtersString = searchParams.get("columnFilters");
    const parsedFilters = filtersString
        ? JSON.parse(decodeURIComponent(filtersString))
        : [];

    // Find current column filter
    const currentFilter = parsedFilters.find((f) => f.id === column.id) || {
        id: column.id,
        value: "",
    };
    const [inputValue, setInputValue] = useState(currentFilter.value);

    // Debounced function to update URL while keeping other params
    const updateSearchParams = useCallback(
        _.debounce((value) => {
            let updatedFilters = parsedFilters.filter(
                (f) => f.id !== column.id
            );
            if (value) {
                updatedFilters.push({ id: column.id, value });
            }

            // Preserve other query params
            const newParams = new URLSearchParams(existingParams);
            newParams.set(
                "columnFilters",
                encodeURIComponent(JSON.stringify(updatedFilters))
            );
            newParams.set("page", 1);
            newParams.set("per_page", 50);

            router.get(url.split("?")[0], Object.fromEntries(newParams), {
                preserveScroll: true,
                preserveState: true,
            });
        }, 500), // Debounce time: 500ms
        [url, parsedFilters, existingParams]
    );

    // Handle input change
    const handleChange = (event) => {
        const value = event.target.value;
        setInputValue(value);
        updateSearchParams(value);
    };

    // Sync state with URL when it changes
    useEffect(() => {
        setInputValue(currentFilter.value);
    }, [filtersString]);

    return (
        <TextField
            disabled={column.disableSearch}
            id={column.id}
            size="small"
            autoComplete="off"
            value={inputValue}
            onChange={handleChange}
            label={column.label}
        />
    );
};
