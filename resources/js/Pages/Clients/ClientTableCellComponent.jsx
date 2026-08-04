import CallDialog from "@/Call/CallDialog";
import { Table } from "@devexpress/dx-react-grid-material-ui";
import { Link } from "@inertiajs/react";
import { DraftsRounded, DraftsTwoTone, EditRounded } from "@mui/icons-material";
import {
    IconButton,
    Chip as MuiChip,
    Stack,
    Typography,
    styled,
} from "@mui/material";
import { green } from "@mui/material/colors";
import LinkedInUrl from "./CellComponents/LinkedInUrl";

export default function ClientTableCellComponent(props) {
    const id = props.row.id;

    if (props.column.name === "linkdinurl") {
        return (
            <Table.Cell {...props}>
                {props.value ? (
                    <LinkedInUrl
                        url={props.value}
                        name={`${props.row.fname} ${props.row.lname}`}
                    />
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "designation") {
        return (
            <Table.Cell {...props}>
                {props.value && (
                    <Chip
                        clickable
                        sx={{ borderRadius: 1 }}
                        size="small"
                        label={props.value}
                    />
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "clientPhone") {
        const { clientPhone } = props.row;

        return (
            <Table.Cell {...props}>
                {clientPhone ? (
                    <CallDialog
                        phone={clientPhone}
                        name={clientPhone}
                        id={id}
                    />
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "ClientEmail") {
        const { ClientEmail } = props.row;
        return (
            <Table.Cell {...props}>
                {ClientEmail ? (
                    <>
                        <Stack direction="row" spacing={1}>
                            <DraftsTwoTone fontSize="small" color="success" />
                            <Typography fontSize={16} color="primary.main">
                                {ClientEmail}
                            </Typography>
                        </Stack>
                    </>
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "actions") {
        return (
            <Table.Cell {...props}>
                <Stack
                    direction="row"
                    alignItems="center"
                    justifyContent="flex-end"
                >
                    <IconButton
                        size="small"
                        LinkComponent={Link}
                        href={route("client.edit", { id: id })}
                    >
                        <EditRounded
                            fontSize="small"
                            sx={{ ":hover": { color: "primary.main" } }}
                        />
                    </IconButton>
                </Stack>
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}

const Chip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${green[100]};
    color: ${green[900]};
    &:hover {
        background-color: ${green[200]};
        color: ${green[900]};
    }
`;
