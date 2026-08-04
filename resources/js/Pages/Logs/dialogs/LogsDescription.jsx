import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    TextField,
} from "@mui/material";

export const LogsDescription = ({ open, handleClose, DescriptionData }) => {
    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="sm"
            fullWidth
        >
            <DialogTitle>Description Data</DialogTitle>

            <DialogContent dividers>
                {DescriptionData && (
                    <TextField
                        label="Description"
                        multiline
                        rows={4}
                        value={DescriptionData}
                    />
                )}
            </DialogContent>

            <DialogActions>
                <Button onClick={handleClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
