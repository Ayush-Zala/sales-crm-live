import { Box, Snackbar } from "@mui/material";
import React from "react";

const SnackBar = ({ open, message }) => {
    return (
        <Box sx={{ width: 500 }}>
            <Snackbar
                anchorOrigin={{ vertical: "top", horizontal: "right" }}
                open={open}
                message={message}
            />
        </Box>
    );
};

export default SnackBar;
