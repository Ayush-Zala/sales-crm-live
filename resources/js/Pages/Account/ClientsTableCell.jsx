import { DraftsTwoTone } from "@mui/icons-material";
import { Button, Stack, Typography } from "@mui/material";

import CallDialog from "@/Call/CallDialog";
import LinkedInUrl from "../Clients/CellComponents/LinkedInUrl";
import ClientsTableActions from "./CellComponents/ClientsTableActions";
import ClientBlacklistSwitch from "./CellComponents/ClientBlacklistSwitch";

export default function ClientsTableCell({ row, column }) {
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
                        phoneType={phone.phonetype || ""}
                        name={row.name}
                        id={row.companyId}
                        clientId={row.clientid}
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
                                {`${email} (${email.emailtype || ""})`}
                            </Typography>
                        </Button>
                    </Stack>
                ))
            );
        case "blacklisted":
            return <ClientBlacklistSwitch client={row} />;
        case "designation":
            return row.designation;
        case "linkedinurl":
            return (
                row.linkedin_url && (
                    <LinkedInUrl url={row.linkedin_url} name={row.name} />
                )
            );
        case "actions":
            return <ClientsTableActions row={row} />;
        default:
            return null;
    }
}
