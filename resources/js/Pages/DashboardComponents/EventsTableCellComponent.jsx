import { formatDateTime } from "@/utils/date-time-formatters";
import styled from "@emotion/styled";
import { BusinessRounded, DescriptionSharp } from "@mui/icons-material";
import {
    Box,
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Chip as MuiChip,
} from "@mui/material";
import { green, orange, pink } from "@mui/material/colors";
import { Fragment, useState } from "react";

export default function EventsTableCellComponent({ row, column }) {
    const [descriptionDialog, setDescriptionDialog] = useState(false);
    const [repeatRuleDialog, setRepeatRuleDialog] = useState(false);

    const handleDescriptionDialogOpen = () => setDescriptionDialog(true);
    const handleDescriptionDialogClose = () => setDescriptionDialog(false);

    const handleRepeatRuleDialogOpen = () => setRepeatRuleDialog(true);
    const handleRepeatRuleDialogClose = () => setRepeatRuleDialog(false);

    switch (column.id) {
        case "title":
            return row.title;
        case "company_name":
            return (
                row.company_id && (
                    <IconButton
                        size="small"
                        aria-hidden="false"
                        LinkComponent={"a"}
                        href={route("account.view", row.company_id)}
                        target="_blank"
                    >
                        <BusinessRounded
                            fontSize="small"
                            sx={{ color: "primary.main" }}
                        />

                        <Chip
                            size="small"
                            label={row.company_name}
                            sx={{ ml: 1 }}
                            bgColor={orange[100]}
                            textColor={orange[800]}
                            bgHoverColor={orange[800]}
                            bgTextColor={orange[100]}
                        />
                    </IconButton>
                )
            );
        case "user_name":
            return (
                row.user_name && (
                    <Chip
                        clickable
                        size="small"
                        label={row.user_name}
                        sx={{ borderRadius: 1 }}
                        bgColor={pink[100]}
                        textColor={pink[800]}
                        bgHoverColor={pink[800]}
                        bgTextColor={pink[100]}
                    />
                )
            );
        case "description":
            return (
                row.description && (
                    <Fragment>
                        <IconButton size="small" aria-hidden="false">
                            <DescriptionSharp
                                fontSize="small"
                                onClick={handleDescriptionDialogOpen}
                                sx={{ color: "primary.main" }}
                            />
                        </IconButton>

                        <InfoDialog
                            data={row.description}
                            title="Description of the Event"
                            label="Description"
                            open={descriptionDialog}
                            handleClose={handleDescriptionDialogClose}
                        />
                    </Fragment>
                )
            );
        case "start_date":
            return (
                row.start_date && (
                    <Chip
                        clickable
                        size="small"
                        label={formatDateTime(row.start_date)}
                        sx={{ borderRadius: 1 }}
                    />
                )
            );
        case "end_date":
            return (
                row.end_date && (
                    <Chip
                        clickable
                        size="small"
                        label={formatDateTime(row.end_date)}
                        sx={{ borderRadius: 1 }}
                    />
                )
            );
        case "timezone":
            return row.timezone;
        case "repeat_rule":
            const handleOpen = () => {
                setOpen(true);
            };
            const handleClose = () => {
                setOpen(false);
            };

            return (
                row.repeat_rule && (
                    <Fragment>
                        <IconButton size="small" aria-hidden="false">
                            <DescriptionSharp
                                fontSize="small"
                                onClick={handleRepeatRuleDialogOpen}
                                sx={{ color: "primary.main" }}
                            />
                        </IconButton>

                        <InfoDialog
                            data={row.repeat_rule}
                            title="Repeat Rule of the Event"
                            label="Repeat Rule"
                            open={repeatRuleDialog}
                            handleClose={handleRepeatRuleDialogClose}
                        />
                    </Fragment>
                )
            );
        case "all_day":
            return row.all_day ? "Yes" : "No";
        default:
            return null;
    }
}

const Chip = styled(MuiChip)(
    ({
        bgColor = green[100],
        textColor = green[800],
        bgHoverColor = green[800],
        bgTextColor = green[100],
    }) => ({
        fontWeight: 600,
        backgroundColor: bgColor,
        color: textColor,
        "&:hover": {
            backgroundColor: bgHoverColor,
            color: bgTextColor,
        },
    })
);

export const InfoDialog = ({ open, handleClose, data, title, label }) => {
    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="sm"
            fullWidth
        >
            <DialogTitle>{title}</DialogTitle>

            <DialogContent dividers>
                {/* <TextField
                    // label={label}
                    multiline
                    rows={5}
                    dangerouslySetInnerHTML={{ __html: data }}
                /> */}

                <Box
                    sx={{
                        border: "1px solid rgba(0, 0, 0, 0.23)",
                        borderRadius: "4px",
                        padding: "12px 14px",
                        minHeight: "150px",
                        overflow: "auto",
                        whiteSpace: "pre-wrap", // To maintain the spaces and line breaks in HTML
                    }}
                    dangerouslySetInnerHTML={{ __html: data }} // Render the HTML safely
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
