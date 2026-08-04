import { MailIcon } from "lucide-react";

import Link from "@mui/material/Link";
import Button from "@mui/material/Button";
import Stack from "@mui/material/Stack";
import { usePage } from "@inertiajs/react";
import { hasRole } from "@/utils/AccessManager";
import { Typography } from "@mui/material";

export const EmailAddress = ({ email }) => {
    const { auth } = usePage().props;

    const isDEM = hasRole(auth, ["Data Entry Manager", "Data Entry"]);

    return (
        <Stack alignItems="flex-start">
            {!isDEM ? (
                <Button
                    startIcon={<MailIcon size={18} />}
                    LinkComponent={Link}
                    variant="text"
                    size="small"
                    href={`mailto:${email}`}
                    sx={{ textTransform: "none" }}
                >
                    {email}
                </Button>
            ) : (
                <Typography
                    fontSize="inherit"
                    color="primary.main"
                    textTransform="lowercase"
                >
                    {email}
                </Typography>
            )}
        </Stack>
    );
};
