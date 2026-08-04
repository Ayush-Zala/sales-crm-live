import {
    Grid,
    Table,
    TableFixedColumns,
    TableHeaderRow,
} from "@devexpress/dx-react-grid-material-ui";
import { Paper, TableContainer } from "@mui/material";
import { concat, filter, lowerCase, map } from "lodash";
import PropTypes from "prop-types";
import { AmountFormatter } from "./amount-formatter";
import { DataTimeFormatter } from "./data-time-formatter";
import { Loading } from "./loading";
import { MuiTable } from "./mui-table";
import { NoDataCell } from "./no-data-cell";

const CustomTableRow = ({ row, handleRowClick, ...restProps }) => (
    <Table.Row
        {...restProps}
        onClick={handleRowClick ? () => handleRowClick(row) : undefined}
        style={handleRowClick ? { cursor: "pointer" } : undefined} // Optional styling for better UX
    />
);

export const Datatable = ({
    loading,
    columns,
    rows,
    error,
    cellComponent,
    columnExtensions,
    handleRowClick,
}) => {
    const leftColumns = map(
        filter(columns, (column) => lowerCase(column.title) === "id"),
        "name"
    );

    const rightColumns = map(
        filter(columns, (column) => lowerCase(column.title) === "actions"),
        "name"
    );

    return (
        <TableContainer
            component={Paper}
            sx={{ position: "relative", maxHeight: 350 }}
        >
            <Loading loading={loading} />
            <Grid rows={rows} columns={columns}>
                <DataTimeFormatter for={["createdAt", "updatedAt"]} />
                <AmountFormatter for={["price"]} />
                <Table
                    cellComponent={cellComponent}
                    columnExtensions={columnExtensions}
                    messages={{ noData: error || "No data available" }}
                    noDataCellComponent={(props) => (
                        <NoDataCell {...props} loading={loading} />
                    )}
                    tableComponent={({ forwardedRef, ...props }) => (
                        <MuiTable ref={forwardedRef} {...props} />
                    )}
                    rowComponent={({ row, ...props }) => (
                        <CustomTableRow
                            {...props}
                            row={row}
                            handleRowClick={handleRowClick} // Pass the row click handler
                        />
                    )}
                />
                <TableHeaderRow />
                <TableFixedColumns
                    leftColumns={leftColumns}
                    rightColumns={rightColumns}
                />
            </Grid>
        </TableContainer>
    );
};

Datatable.defaultProps = {
    loading: false,
    columns: [],
    rows: [],
    error: null,
    cellComponent: null,
};

Datatable.propTypes = {
    loading: PropTypes.bool.isRequired,
    columns: PropTypes.arrayOf(
        PropTypes.shape({
            name: PropTypes.string.isRequired,
            title: PropTypes.string.isRequired,
        })
    ).isRequired,
    error: PropTypes.string,
    rows: PropTypes.array.isRequired,
};
