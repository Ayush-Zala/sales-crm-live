import { Datatable } from "@/Components/datatable";
import { Grid, Typography } from "@mui/material";
import ClientLeadTableCell from "./ClientLeadTableCell";

export default function ClientLeadTable({ clients }) {
    const columns = [
        { name: "clientid", title: "ID" },
        { name: "clientname", title: "Name" },
        { name: "phones", title: "Phone" },
        { name: "emails", title: "Email" },
        { name: "designation", title: "Designation" },
        { name: "linkedinurl", title: "Linkedin URL" },
    ];

    return (
        <Grid item xs={12} mt={2}>
            <Typography variant="h6" sx={{ mb: 1 }}>
                Lead Clients
            </Typography>

            <Datatable
                columns={columns}
                rows={clients}
                cellComponent={ClientLeadTableCell}
            />
        </Grid>
    );
}
