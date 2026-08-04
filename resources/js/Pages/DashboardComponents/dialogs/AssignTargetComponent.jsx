import { Datatable } from "@/Components/datatable";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    TextField,
} from "@mui/material";
import React from "react";
import AssignTargetCellComponent from "../CellComponents/AssignTargetCellComponent";

export const AssignTargetComponent = ({ open, handleClose, targets }) => {
    const columns = [
        { name: "name", title: "Name" },
        { name: "target_achieved", title: "Target" },
    ];

    const columnExtensions = [
        { columnName: "name", title: "Name", width: 300 },
        { columnName: "target", title: "Target", width: 200 },
    ];

    return (
        <Dialog
            fullWidth
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <DialogTitle>{`Assign Targets (${targets[0]?.time})`}</DialogTitle>
            <DialogContent sx={{ padding: 0 }}>
                <Datatable
                    columns={columns} // Pass the columns here
                    rows={targets} // Pass the dynamically converted rows here
                    cellComponent={AssignTargetCellComponent} // Pass the cell component
                    columnExtensions={columnExtensions} // Pass the column extensions here
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
