import Paper from "@mui/material/Paper";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import TablePaginationComponent from "./tableComponents/TablePagination";
import theme from "@/theme";

const PaginatedTable = ({
    columns,
    rows,
    current_page,
    per_page,
    total,
    url,
    CellComponent,
}) => {
    return (
        <Paper sx={{ width: "100%", overflow: "hidden" }}>
            <TableContainer sx={{ maxHeight: `calc(100vh - 270px)` }}>
                <Table stickyHeader size="small">
                    <TableHead>
                        <TableRow>
                            {columns.map((column) => (
                                <TableCell
                                    key={column.id}
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
                                            : column.align === "left"
                                            ? {
                                                  position: "sticky",
                                                  left: 0,
                                                  backgroundColor: (theme) =>
                                                      theme.palette.background
                                                          .paper,
                                                  whiteSpace: "nowrap",
                                                  borderRight: "1px solid",
                                                  borderColor: (theme) =>
                                                      theme.palette.divider,
                                                  width: column.width || "auto",
                                                  zIndex:
                                                      theme.zIndex.appBar + 2,
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
                        {rows.map((row) => (
                            <TableRow key={row.id}>
                                {columns.map((column) => (
                                    <TableCell
                                        key={column.id}
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
                                                              .background.paper,
                                                      whiteSpace: "nowrap",
                                                      borderLeft: "1px solid",
                                                      borderColor: (theme) =>
                                                          theme.palette.divider,
                                                      width:
                                                          column.width ||
                                                          "auto",
                                                  }
                                                : column.align === "left"
                                                ? {
                                                      position: "sticky",
                                                      left: 0,
                                                      backgroundColor: (
                                                          theme
                                                      ) =>
                                                          theme.palette
                                                              .background.paper,
                                                      whiteSpace: "nowrap",
                                                      borderRight: "1px solid",
                                                      borderColor: (theme) =>
                                                          theme.palette.divider,
                                                      width:
                                                          column.width ||
                                                          "auto",
                                                      zIndex:
                                                          theme.zIndex.appBar +
                                                          1,
                                                  }
                                                : {
                                                      whiteSpace: "nowrap",
                                                      width:
                                                          column.width ||
                                                          "auto",
                                                      textAlign:
                                                          column.textAlign ||
                                                          "center",
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
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>
            <TablePaginationComponent
                current_page={current_page}
                per_page={per_page}
                total={total}
                url={url}
            />
        </Paper>
    );
};

export default PaginatedTable;
