import { useLayoutStore } from "@/store/layout-store";
import { hasRole,hasPermission } from "@/utils/AccessManager";

import { Link, usePage } from "@inertiajs/react";
import {
    AdminPanelSettingsRounded,
    BusinessRounded,
    ContactsRounded,
    DashboardRounded,
    Diversity2Rounded,
    EventNoteRounded,
    NotificationsActiveRounded,
    PeopleRounded,
    PublicRounded,
    RecentActorsRounded,
    ReportGmailerrorredRounded,
    ShieldRounded,
    StoreRounded,
    SummarizeRounded,
} from "@mui/icons-material";
import {
    List,
    ListItemButton,
    ListItemIcon,
    ListItemText,
    Tooltip,
} from "@mui/material";

const SidebarMenuList = () => {
    const open = useLayoutStore((state) => state.open);

    const { auth } = usePage().props;
    const isAdmin = hasRole(auth, ["Admin"]);
    const isBDManager = hasRole(auth, ["Business Development Manager"]);
    const isBDTeamLead = hasRole(auth, ["Business Development Team Lead"]);
    const isDEM = hasRole(auth, ["Data Entry Manager"]);
    const isDE = hasRole(auth, ["Data Entry"]);
    const isCSRRole = hasRole(auth, [
        "Customer Service Representative Manager",
        "Customer Service Representative Team Lead",
        "Customer Service Representative",
    ]);

    const currentRoute = route().current();
    const hasViewLeadsPermission = auth.permissions.includes("Can View Lead");
    const hasViewRetentionPermission = auth.permissions.includes("Can View Retention");

    const sideBar = isAdmin
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              {
                  name: "Leads",
                  icon: (props) => <StoreRounded {...props} />,
                  link: "lead.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Event Managment",
                  icon: (props) => <EventNoteRounded {...props} />,
                  link: "event.index",
              },
              {
                  name: "Users",
                  icon: (props) => <PeopleRounded {...props} />,
                  link: "user",
              },
              {
                  name: "Permissions",
                  icon: (props) => <AdminPanelSettingsRounded {...props} />,
                  link: "permission.index",
              },
              {
                  name: "Roles",
                  icon: (props) => <ShieldRounded {...props} />,
                  link: "role.index",
              },
              {
                  name: "Groups",
                  icon: (props) => <Diversity2Rounded {...props} />,
                  link: "group.index",
              },
              //   {
              //       name: "Emails",
              //       icon: (props) => <EmailRounded {...props} />,
              //       link: "email.index",
              //   },
              {
                  name: "Notifications",
                  icon: (props) => <NotificationsActiveRounded {...props} />,
                  link: "notification.index",
              },
              {
                  name: "Zoom Calls",
                  icon: (props) => <ContactsRounded {...props} />,
                  link: "zoom.calllogs",
              },
              {
                  name: "Zoom Meeting Logs",
                  icon: (props) => <RecentActorsRounded {...props} />,
                  link: "zoom.meetings",
              },
              {
                  name: "Logs",
                  icon: (props) => <ReportGmailerrorredRounded {...props} />,
                  link: "logs.index",
              },
              {
                  name: "Reports",
                  icon: (props) => <SummarizeRounded {...props} />,
                  link: "report.index",
              },
          ]
        : isBDManager
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              {
                  name: "Leads",
                  icon: (props) => <StoreRounded {...props} />,
                  link: "lead.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Event Managment",
                  icon: (props) => <EventNoteRounded {...props} />,
                  link: "event.index",
              },
              {
                  name: "Users",
                  icon: (props) => <PeopleRounded {...props} />,
                  link: "user",
              },
              //   {
              //       name: "Emails",
              //       icon: (props) => <EmailRounded {...props} />,
              //       link: "email.index",
              //   },
              {
                  name: "Notifications",
                  icon: (props) => <NotificationsActiveRounded {...props} />,
                  link: "notification.index",
              },
              {
                  name: "Logs",
                  icon: (props) => <ReportGmailerrorredRounded {...props} />,
                  link: "logs.index",
              },
              {
                  name: "Reports",
                  icon: (props) => <SummarizeRounded {...props} />,
                  link: "report.index",
              },
          ]
        : isBDTeamLead
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              {
                  name: "Leads",
                  icon: (props) => <StoreRounded {...props} />,
                  link: "lead.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Event Managment",
                  icon: (props) => <EventNoteRounded {...props} />,
                  link: "event.index",
              },
              {
                  name: "Logs",
                  icon: (props) => <ReportGmailerrorredRounded {...props} />,
                  link: "logs.index",
              },
              {
                  name: "Reports",
                  icon: (props) => <SummarizeRounded {...props} />,
                  link: "report.index",
              },
              //   {
              //       name: "Emails",
              //       icon: (props) => <EmailRounded {...props} />,
              //       link: "email.index",
              //   },
          ]
        : isDEM
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              {
                  name: "Leads",
                  icon: (props) => <StoreRounded {...props} />,
                  link: "lead.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Reports",
                  icon: (props) => <SummarizeRounded {...props} />,
                  link: "report.index",
              },
          ]
        : isDE
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
          ]
        : isCSRRole
        ? [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
 ...(hasViewLeadsPermission
                                    ? [{
                                        name: "Leads",
                                        icon: (props) => <StoreRounded {...props} />,
                                        link: "lead.index",
                                    }]
                                    : []),
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Countries",
                  icon: (props) => <PublicRounded {...props} />,
                  link: "country.index",
              },
          ]
        : [
              {
                  name: "Dashboard",
                  icon: (props) => <DashboardRounded {...props} />,
                  link: "dashboard",
              },
              {
                  name: "Accounts",
                  icon: (props) => <BusinessRounded {...props} />,
                  link: "account.index",
              },
              {
                  name: "Leads",
                  icon: (props) => <StoreRounded {...props} />,
                  link: "lead.index",
              },
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              ...(hasViewRetentionPermission
                                    ? [{
                                        name: "Retention",
                                        icon: (props) => <BusinessRounded {...props} />,
                                        link: "retention.index",
                                    }]
                                    : []),
              {
                  name: "Event Managment",
                  icon: (props) => <EventNoteRounded {...props} />,
                  link: "event.index",
              },
              {
                  name: "Countries",
                  icon: (props) => <PublicRounded {...props} />,
                  link: "country.index",
              },
              {
                  name: "Reports",
                  icon: (props) => <SummarizeRounded {...props} />,
                  link: "report.index",
              },
          ];

    return (
        <List
            dense
            sx={{
                position: "relative",
                overflow: open && "auto",
                "& ul": { padding: 0 },
            }}
        >
            {sideBar.map((item, index) => (
                <Tooltip
                    title={open ? null : item.name}
                    placement="right"
                    key={index}
                >
                    <ListItemButton
                        LinkComponent={Link}
                        disableRipple
                        disableTouchRipple
                        selected={currentRoute === item.link}
                        href={route(item.link)}
                        divider
                        sx={{
                            minHeight: 48,
                            px: 2.5,
                            justifyContent: open ? "initial" : "center",
                        }}
                    >
                        <ListItemIcon
                            sx={{
                                minWidth: 0,
                                mr: open ? 3 : "auto",
                                justifyContent: "center",
                            }}
                        >
                            <item.icon
                                color={
                                    item.name === "Logout" ? "error" : "primary"
                                }
                            />
                        </ListItemIcon>
                        <ListItemText
                            primary={item.name}
                            sx={{
                                opacity: open ? 1 : 0,
                                transition: "opacity 0.2s ease-in-out",
                            }}
                            primaryTypographyProps={{
                                fontWeight: 700,
                                // color: isActived
                                //     ? "primary.main"
                                //     : "text.primary",
                            }}
                        />
                    </ListItemButton>
                </Tooltip>
            ))}
        </List>
    );
};

export default SidebarMenuList;
