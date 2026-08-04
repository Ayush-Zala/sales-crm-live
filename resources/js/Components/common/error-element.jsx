import { Stack, Typography } from "@mui/material";
import Lottie from "lottie-react";
import _404 from "@/assets/lottie/404.json";

export const ErrorElement = ({ error }) => {
    return (
        <Stack alignItems="center" py={2}>
            <Lottie
                loop
                autoPlay
                animationData={_404}
                style={{ height: 200 }}
            />
            <Typography
                color="secondary.main"
                variant="subtitle2"
                textAlign="center"
            >
                {error}
            </Typography>
        </Stack>
    );
};
