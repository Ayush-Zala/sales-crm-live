import { Skeleton } from "@mui/material";
import Paper from "@mui/material/Paper";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";

const SimpleDataTable = ({
    columns,
    rows,
    tableMaxHeight,
    CellComponent,
    noDataMessage,
    noDataMessageBoxHeight,
    clickableRow,
    handleClickRow,
    isLoading, // Add a prop to handle loading state
    skeletonRows = 6, // Default number of skeleton rows
}) => {
    return (
        <Paper sx={{ width: "100%", overflow: "hidden" }}>
            <TableContainer sx={{ maxHeight: tableMaxHeight }}>
                <Table stickyHeader size="small">
                    <TableHead>
                        <TableRow>
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
                                                  width: column.width || "auto",
                                              }
                                            : {
                                                  whiteSpace: "nowrap",
                                                  width: column.width || "auto",
                                              }
                                    }
                                >
                                    {column.label}
                                </TableCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {isLoading ? (
                            // Show skeletons while loading
                            Array.from({ length: skeletonRows }).map(
                                (_, rowIndex) => (
                                    <TableRow key={rowIndex}>
                                        {columns.map((column, colIndex) => (
                                            <TableCell key={colIndex}>
                                                <Skeleton
                                                    variant="rectangular"
                                                    width="100%"
                                                    height={24}
                                                />
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                )
                            )
                        ) : rows.length > 0 ? (
                            rows.map((row, rowIndex) => (
                                <TableRow
                                    key={rowIndex}
                                    hover={clickableRow ? true : false}
                                    role={clickableRow ? "checkbox" : ""}
                                    sx={{
                                        ":hover": {
                                            cursor: clickableRow && "pointer",
                                        },
                                    }}
                                    onClick={
                                        handleClickRow
                                            ? () => handleClickRow(row)
                                            : null
                                    }
                                >
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
                                                          width:
                                                              column.width ||
                                                              "auto",
                                                      }
                                                    : {
                                                          whiteSpace: "nowrap",
                                                          width:
                                                              column.width ||
                                                              "auto",
                                                      }
                                            }
                                        >
                                            <CellComponent
                                                row={row}
                                                column={column}
                                            />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    align="center"
                                    sx={{
                                        fontStyle: "italic",
                                        color: "gray",
                                        padding: 4,
                                        height: noDataMessageBoxHeight || 150,
                                    }}
                                >
                                    {noDataMessage || "No data available"}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>
        </Paper>
    );
};

export default SimpleDataTable;
