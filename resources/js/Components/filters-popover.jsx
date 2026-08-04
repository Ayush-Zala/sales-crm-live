import {
    Box,
    Divider,
    IconButton,
    Popover,
    Stack,
    Typography,
} from "@mui/material";
import { X } from "lucide-react";

export const FiltersPopover = ({ title, anchorEl, handleClose, children }) => {
    const open = Boolean(anchorEl);

    const id = open ? `${title}-filters-popover` : undefined;

    return (
        <Popover
            id={id}
            open={open}
            anchorEl={anchorEl}
            onClose={handleClose}
            slotProps={{ paper: { sx: { minWidth: 420 } } }}
        >
            <Stack
                direction="row"
                alignItems="center"
                justifyContent="space-between"
                sx={{ px: 2, py: 1 }}
            >
                <Typography
                    color="text.secondary"
                    variant="body2"
                    fontWeight="600"
                >
                    {title}
                </Typography>
                <IconButton size="small" color="error" onClick={handleClose}>
                    <X size={18} strokeWidth={1} />
                </IconButton>
            </Stack>
            <Divider />
            <Box
                p={2}
                maxHeight={380}
                height="inherit"
                sx={{ overflowY: "auto", height: "inherit" }}
            >
                {children}
            </Box>
        </Popover>
    );
};
