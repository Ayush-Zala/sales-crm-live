import { Table } from "@devexpress/dx-react-grid-material-ui";
import { DescriptionSharp } from "@mui/icons-material";
import { IconButton } from "@mui/material";
import { useState } from "react";
import { LogsDescription } from "./dialogs/LogsDescription";
import { LogsNewDataComponent } from "./dialogs/LogsNewDataComponent";
import { LogsOldDataComponent } from "./dialogs/LogsOldDataComponent";

export default function LogsTableCellComponent(props) {
    if (props.column.title === "Old Data") {
        const oldData = JSON.parse(props.value);

        const [open, setOpen] = useState(false);

        const handleOpen = () => {
            setOpen(true);
        };
        const handleClose = () => {
            setOpen(false);
        };

        return (
            <Table.Cell {...props}>
                <IconButton size="small" aria-hidden="false">
                    <DescriptionSharp
                        fontSize="small"
                        onClick={handleOpen}
                        sx={{ color: "orange" }}
                    />
                </IconButton>

                <LogsOldDataComponent
                    oldData={oldData.attributes}
                    open={open}
                    handleClose={handleClose}
                />
            </Table.Cell>
        );
    }

    if (props.column.title === "New Data") {
        const newData = JSON.parse(props.value);

        const [open, setOpen] = useState(false);

        const handleOpen = () => {
            setOpen(true);
        };
        const handleClose = () => {
            setOpen(false);
        };

        return (
            <Table.Cell {...props}>
                <IconButton size="small" aria-hidden="false">
                    <DescriptionSharp
                        fontSize="small"
                        onClick={handleOpen}
                        sx={{ color: "green" }}
                    />
                </IconButton>

                <LogsNewDataComponent
                    newData={newData.attributes}
                    open={open}
                    handleClose={handleClose}
                />
            </Table.Cell>
        );
    }
    if (props.column.name === "description") {
        const DescriptionData = props.value;

        const [open, setOpen] = useState(false);

        const handleOpen = () => {
            setOpen(true);
        };
        const handleClose = () => {
            setOpen(false);
        };

        return (
            <Table.Cell {...props}>
                <IconButton size="small" aria-hidden="false">
                    <DescriptionSharp
                        fontSize="small"
                        onClick={handleOpen}
                        sx={{ color: "primary.main" }}
                    />
                </IconButton>

                <LogsDescription
                    DescriptionData={DescriptionData}
                    open={open}
                    handleClose={handleClose}
                />
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
