import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    TextField,
} from "@mui/material";

export const LogsNewDataComponent = ({ open, handleClose, newData }) => {
    const keysNewData = Object.keys(newData);

    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="sm"
        >
            <DialogTitle>New Data</DialogTitle>

            <DialogContent dividers>
                <Grid container xs={12} spacing={2}>
                    {newData &&
                        keysNewData.map((key) => (
                            <Grid item xs={6} key={key}>
                                <TextField
                                    id={key}
                                    label={key}
                                    value={newData[key]}
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
