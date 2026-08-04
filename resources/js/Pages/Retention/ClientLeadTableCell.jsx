import CallDialog from "@/Call/CallDialog";
import { DraftsTwoTone } from "@mui/icons-material";
import { Button, Stack, Typography } from "@mui/material";
import LinkedInUrl from "./CellComponents/LinkedInUrl";

export default function ClientLeadTableCell({ row, column }) {
    switch (column.id) {
        case "clientname":
            return row.name;
        case "phones":
            return (
                row.phones &&
                row.phones.map((phone, index) => (
                    <CallDialog
                        key={index}
                        phone={phone}
                        phoneType={phone.type || ""}
                        name={row.name}
                        id={row.companyid}
                        clientId={row.clientid}
                        apiDataRoute="retention.windowrefreshdisposition"
                        submitDispositionRoute="retention.submitleaddisposition"
                        historyRoute="retention.getleadcallhistory"
                    />
                ))
            );
        case "emails":
            return (
                row.emails &&
                row.emails.map((email, index) => (
                    <Stack direction="row" spacing={1} key={index}>
                        <Button
                            variant="text"
                            size="small"
                            startIcon={
                                <DraftsTwoTone
                                    fontSize="small"
                                    color="success"
                                />
                            }
                            sx={{ textTransform: "none" }}
                        >
                            <Typography fontSize={16} color="primary.main">
                                {`${email} (${email.type || ""})`}
                            </Typography>
                        </Button>
                    </Stack>
                ))
            );
        case "designation":
            return row.designation;
        case "linkedinurl":
            return (
                row.linkedin_url && (
                    <LinkedInUrl url={row.linkedin_url} name={row.name} />
                )
            );
        default:
            return null;
    }
}

// if (props.column.name === "clientname") {
//     return (
//         <Table.Cell {...props}>
//             <Typography variant="body2">
//                 {props.row.firstname} {props.row.lastname}
//             </Typography>
//         </Table.Cell>
//     );
// }

// if (props.column.name === "phones") {
//     return (
//         <Table.Cell {...props}>
//             {props.row.phones ? (
//                 props.row.phones.map((phone, index) => (
//                     <CallDialog
//                         index={index}
//                         phone={phone.phone}
//                         name={`${props.row.firstname} ${props.row.lastname}`}
//                         id={props.row.companyid}
//                         clientId={props.row.clientid}
//                         apiDataRoute="retention.windowrefreshdisposition"
//                         submitDispositionRoute="retention.submitleaddisposition"
//                         historyRoute="retention.getleadcallhistory"
//                     />
//                 ))
//             ) : (
//                 <></>
//             )}
//         </Table.Cell>
//     );
// }

// if (props.column.name === "emails") {
//     return (
//         <Table.Cell {...props}>
//             {props.row.email ? (
//                 props.row.email.map((email, index) => (
//                     <Stack direction="row" spacing={1}>
//                         <DraftsTwoTone fontSize="small" color="success" />
//                         <Typography fontSize={16} color="primary.main">
//                             {props.row.email}
//                         </Typography>
//                     </Stack>
//                 ))
//             ) : (
//                 <></>
//             )}
//         </Table.Cell>
//     );
// }

// if (props.column.name === "linkedinurl") {
//     return (
//         <Table.Cell {...props}>
//             {props.value ? (
//                 <LinkedInUrl
//                     url={props.value}
//                     name={`${props.row.firstname} ${props.row.lastname}`}
//                 />
//             ) : (
//                 <></>
//             )}
//         </Table.Cell>
//     );
// }

// return <Table.Cell {...props} />;
