import { format } from "date-fns";

import Typography from "@mui/material/Typography";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import Paper from "@mui/material/Paper";
import { usePage } from "@inertiajs/react";
import { hasPermission } from "@/utils/AccessManager";

const DispositionsTable = ({ dispositions }) => {
    const { auth } = usePage().props;
    const hasPerm = hasPermission(auth, "Can View Company Phone");

    return (
        <>
            <Typography variant="h6" component="h6" my={2}>
                Dispositions History
            </Typography>

            <TableContainer component={Paper}>
                <Table size="small" aria-label="a dense table">
                    <TableHead>
                        <TableRow>
                            <TableCell>Status</TableCell>
                            <TableCell>Description</TableCell>
                            <TableCell align="right">Phone Number</TableCell>
                            <TableCell>Timezone</TableCell>
                            <TableCell align="right">Followup Date</TableCell>
                            <TableCell align="right">Followup Time</TableCell>
                            <TableCell align="right">Created By</TableCell>
                            <TableCell align="right">Created At</TableCell>
                            <TableCell align="right">Updated At</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {dispositions.map((row) => (
                            <TableRow
                                key={row.id}
                                sx={{
                                    "&:last-child td, &:last-child th": {
                                        border: 0,
                                    },
                                }}
                            >
                                <TableCell>{row.status}</TableCell>
                                <TableCell component="th" scope="row">
                                    {row.description}
                                </TableCell>
                                <TableCell align="right">
                                    {hasPerm
                                        ? row.phone
                                        : row.client_name
                                        ? row.client_name
                                        : row.company_name}
                                </TableCell>
                                <TableCell>{row.timezone}</TableCell>
                                <TableCell align="right">
                                    {row.followup_date
                                        ? format(
                                              new Date(row.followup_date),
                                              "dd MMMM yyyy"
                                          )
                                        : ""}
                                </TableCell>
                                <TableCell align="right">
                                    {row.followup_time}
                                </TableCell>
                                <TableCell align="right">
                                    {row.user_name}
                                </TableCell>
                                <TableCell align="right">
                                    {row.created_at
                                        ? format(
                                              new Date(row.created_at),
                                              "dd MMMM yyyy pp"
                                          )
                                        : ""}
                                </TableCell>
                                <TableCell align="right">
                                    {row.updated_at
                                        ? format(
                                              new Date(row.updated_at),
                                              "dd MMMM yyyy pp"
                                          )
                                        : ""}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>
        </>
    );
};

export default DispositionsTable;
