import CallDialog from "@/Call/CallDialog";
import { Table } from "@devexpress/dx-react-grid-material-ui";
import { DraftsTwoTone } from "@mui/icons-material";
import { Stack, Typography } from "@mui/material";
import LinkedInUrl from "../Clients/CellComponents/LinkedInUrl";

export default function ClientLeadTableCell(props) {
    if (props.column.name === "clientname") {
        return (
            <Table.Cell {...props}>
                <Typography variant="body2">
                    {props.row.firstname} {props.row.lastname}
                </Typography>
            </Table.Cell>
        );
    }

    if (props.column.name === "phones") {
        return (
            <Table.Cell {...props}>
                {props.row.phones ? (
                    props.row.phones.map((phone, index) => (
                        <CallDialog
                            index={index}
                            phone={phone.phone}
                            name={`${props.row.firstname} ${props.row.lastname}`}
                            id={props.row.companyid}
                            clientId={props.row.clientid}
                            apiDataRoute="lead.windowrefreshdisposition"
                            submitDispositionRoute="lead.submitleaddisposition"
                            historyRoute="lead.getleadcallhistory"
                        />
                    ))
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "emails") {
        return (
            <Table.Cell {...props}>
                {props.row.email ? (
                    props.row.email.map((email, index) => (
                        <Stack direction="row" spacing={1}>
                            <DraftsTwoTone fontSize="small" color="success" />
                            <Typography fontSize={16} color="primary.main">
                                {props.row.email}
                            </Typography>
                        </Stack>
                    ))
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    if (props.column.name === "linkedinurl") {
        return (
            <Table.Cell {...props}>
                {props.value ? (
                    <LinkedInUrl
                        url={props.value}
                        name={`${props.row.firstname} ${props.row.lastname}`}
                    />
                ) : (
                    <></>
                )}
            </Table.Cell>
        );
    }

    return <Table.Cell {...props} />;
}
