import { PhoneInTalkRounded } from "@mui/icons-material";
import {
    Button,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Stack,
    Typography,
} from "@mui/material";
import { useState } from "react";
import toast from "react-hot-toast";

const ShowCallTranscript = ({ recordingId, callId }) => {
    const [transcript, setTranscript] = useState([]);
    const [loading, setLoading] = useState(false);

    const [open, setOpen] = useState(false);

    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    const handleClick = () => {
        setLoading(true);

        fetch(route("zoom.calltranscript", { recordingId: recordingId || 'missing', callId: callId }))
            .then((response) => response.json())
            .then((res) => {
                if (res.data === null || res.data === "") {
                    toast.error("No transcript found for this call");
                    return;
                }

                // convert the transcript to a string in the format of: "Speaker: Transcript" and display it in a dialog
                let transcript = res.data.timeline.map((item) => {
                    return {
                        startTime: item.ts,
                        endTime: item.end_ts,
                        text: item.text,
                        speaker: item.users.map((user) => user.username),
                    };
                });
                setTranscript(transcript);
                handleOpen();
            })
            .catch((error) => {
                console.error(error);
                toast.error("Failed to fetch call transcript");
            })
            .finally(() => {
                setLoading(false);
            });
    };

    return (
        <>
            {(recordingId || callId) ? (
                loading ? (
                    <CircularProgress size={18} color="primary" />
                ) : (
                    <IconButton onClick={handleClick}>
                        <PhoneInTalkRounded color="primary" />
                    </IconButton>
                )
            ) : (
                ""
            )}

            <Dialog
                open={open}
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
                fullWidth
                maxWidth="md"
            >
                <DialogTitle>Call Transcript</DialogTitle>
                <DialogContent dividers>
                    {transcript.map((item, index) => (
                        <Stack
                            key={index}
                            direction="row"
                            mt={index > 0 ? 2 : 0}
                        >
                            <Typography gutterBottom sx={{ width: "40%" }}>
                                <strong>
                                    {`${convertDuration(item.startTime)} -
                                                ${convertDuration(
                                                    item.endTime
                                                )} : (${item.speaker}) `}
                                </strong>
                            </Typography>
                            <Typography sx={{ width: "60%" }}>
                                {item.text}
                            </Typography>
                        </Stack>
                    ))}
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

export default ShowCallTranscript;

function convertDuration(timeString) {
    // Split the time string into hours, minutes, seconds, and milliseconds
    const [hours, minutes, secondsWithMs] = timeString.split(":");
    const [seconds, milliseconds] = secondsWithMs.split(".");

    // Convert everything to total seconds
    const totalSeconds =
        parseInt(hours) * 3600 +
        parseInt(minutes) * 60 +
        parseInt(seconds) +
        parseFloat(`0.${milliseconds}`);

    // Convert total seconds into minutes and seconds
    const minutesPart = Math.floor(totalSeconds / 60);
    const secondsPart = Math.floor(totalSeconds % 60);

    return `${minutesPart}m ${secondsPart}s`;
}
