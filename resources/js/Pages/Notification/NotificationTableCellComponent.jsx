import { Table } from "@devexpress/dx-react-grid-material-ui";
import { IconButton, Stack } from "@mui/material";
import { NotificationDescription } from "./dialogs/NotificationDescription";
import { DescriptionSharp } from "@mui/icons-material";
import { useState } from "react";

export default function NotificationTableCellComponent(props) {
    if (props.column.name === "description") {
        const [open, setOpen] = useState(false);

        const handleOpen = () => {
            setOpen(true);
        };
        const handleClose = () => {
            setOpen(false);
        };

        return (
            <Table.Cell {...props}>
                <Stack
                    direction="row"
                    alignItems="center"
                    justifyContent="flex-start"
                >
                    <IconButton size="small">
                        <DescriptionSharp
                            fontSize="small"
                            onClick={handleOpen}
                            sx={{ color: "primary.main" }}
                        />
                    </IconButton>

                    <NotificationDescription
                        open={open}
                        handleClose={handleClose}
                    />
                </Stack>
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
