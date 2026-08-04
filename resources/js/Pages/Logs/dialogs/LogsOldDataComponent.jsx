import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    TextField,
} from "@mui/material";

export const LogsOldDataComponent = ({ open, handleClose, oldData }) => {
    const keysNewData = Object.keys(oldData);
    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="sm"
        >
            <DialogTitle>Old Data</DialogTitle>

            <DialogContent dividers>
                <Grid container xs={12} spacing={2}>
                    {oldData &&
                        keysNewData.map((key) => (
                            <Grid item xs={6} key={key}>
                                <TextField
                                    id={key}
                                    label={key}
                                    value={oldData[key]}
                                />
                            </Grid>
                        ))}
                </Grid>
            </DialogContent>

            <DialogActions>
                <Button onClick={handleClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
