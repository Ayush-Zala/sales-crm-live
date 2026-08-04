import { ArticleRounded } from "@mui/icons-material";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Stack,
    TextField,
    Tooltip,
    Typography,
} from "@mui/material";
import React from "react";
import { useState } from "react";

const TranscriptDialog = ({ transcript }) => {
    // console.log(JSON.parse(transcript));
    const [open, setOpen] = useState(false);
    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    let parsedTranscript;
    try {
        parsedTranscript = JSON.parse(transcript); // Convert string to object
    } catch (error) {
        parsedTranscript = null; // Handle invalid JSON
    }

    // Function to format duration "00:00:00.000" -> "HH:MM:SS" or "MM:SS"
    const formatDuration = (duration) => {
        if (!duration) return "";

        const parts = duration.split(":"); // Split into [HH, MM, SS.milliseconds]
        if (parts.length < 3) return duration; // Invalid format fallback

        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1], 10);
        const seconds = Math.floor(parseFloat(parts[2])); // Remove milliseconds

        if (hours > 0) {
            return `${hours}h ${minutes}m ${seconds}s`; // "1h 15m 30s"
        } else {
            return `${minutes}m ${seconds}s`; // "15m 30s"
        }
    };

    return (
        <>
            <Tooltip title="View transcript" placement="top" arrow>
                <IconButton color="primary" onClick={handleOpen}>
                    <ArticleRounded />
                </IconButton>
            </Tooltip>

            <Dialog
                fullWidth
                aria-labelledby="customized-dialog-title"
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
                maxWidth="md"
                open={open}
            >
                <DialogTitle>Transcript (Summary)</DialogTitle>
                <DialogContent dividers>
                    {parsedTranscript &&
                    typeof parsedTranscript === "object" ? (
                        Object.entries(parsedTranscript).map(([key, value]) => {
                            if (key === "items") {
                                return value.map((item, index) => (
                                    <Stack
                                        key={index}
                                        direction="row"
                                        mt={index > 0 ? 2 : 0}
                                    >
                                        <Typography
                                            gutterBottom
                                            sx={{ width: "30%" }}
                                        >
                                            <strong>
                                                {`${formatDuration(
                                                    item.start_time
                                                )} -
                                                ${formatDuration(
                                                    item.end_time
                                                )} : `}
                                            </strong>
                                        </Typography>
                                        <Typography sx={{ width: "70%" }}>
                                            {String(
                                                item.summary
                                                    ? item.summary
                                                    : "-"
                                            )}
                                        </Typography>
                                    </Stack>
                                ));
                            }
                        })
                    ) : (
                        <Typography>{transcript}</Typography> // Fallback if not valid JSON
                    )}
                </DialogContent>
                <DialogActions>
                    <Button color="error" onClick={handleClose}>
                        Close
                    </Button>
                </DialogActions>
            </Dialog>
        </>
    );
};

export default TranscriptDialog;
