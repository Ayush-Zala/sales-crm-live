import { Datatable } from "@/Components/datatable";
import { Grid, Typography } from "@mui/material";

import DispositionLeadTableCell from "./DispositionLeadTableCell";

const DispositionLeadTable = ({ dispositions }) => {
    const columns = [
        { name: "dispositionId", title: "ID" },
        { name: "description", title: "Description" },
        { name: "followup_date", title: "Followup Date" },
        { name: "followup_time", title: "Followup Time" },
        { name: "phone", title: "Phone" },
        { name: "timezone", title: "Time zone" },
        { name: "status", title: "Status" },
    ];

    return (
        <Grid item xs={12} mt={2}>
            <Typography variant="h6" sx={{ mb: 1 }}>
                Dispositions
            </Typography>

            <Datatable
                columns={columns}
                rows={dispositions}
                cellComponent={DispositionLeadTableCell}
            />
        </Grid>
    );
};

export default DispositionLeadTable;
