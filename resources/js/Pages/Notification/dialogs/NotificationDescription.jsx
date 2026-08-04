import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    TextField,
} from "@mui/material";

export const NotificationDescription = ({ open, handleClose }) => {
    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="lg"
        >
            <DialogTitle>Description</DialogTitle>
            <DialogContent dividers>
                <TextField
                    id="outlined-multiline-static"
                    label="Multiline"
                    multiline
                    rows={8}
                    defaultValue="Default Value"
                    sx={{ width: 400, height: 200 }}
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
