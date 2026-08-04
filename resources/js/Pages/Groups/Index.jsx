import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import { Fragment, useState } from "react";
import GroupsTableCellComponent from "./GroupsTableCellComponent";
import CreateGroup from "./dialogs/CreateGroup";

const Index = ({ auth, groups }) => {
    const [open, setOpen] = useState(false);
    const handleOpen = () => {
        setOpen(true);
    };
    const handleClose = () => {
        setOpen(false);
    };

    const [columns] = useState([
        { name: "id", title: "ID" },
        { name: "name", title: "Name" },
        { name: "description", title: "Description" },
        { name: "created_at", title: "Create At" },
        { name: "updated_at", title: "Updated At" },
        { name: "actions", title: "Actions" },
    ]);

    return (
        <Fragment>
            <AuthenticatedLayout user={auth.user}>
                <Head title="Groups" />
                <MainContentTemplate
                    title="Groups"
                    subtitle="View groups here"
                    button="Add new group"
                    onClick={handleOpen}
                >
                    <Grid item xs={12}>
                        <PaginatedTable
                            columns={columns}
                            row={groups.data}
                            currentPage={groups.current_page}
                            perPage={groups.per_page}
                            total={groups.total}
                            url="group"
                            tableCellComponent={GroupsTableCellComponent}
                        />
                    </Grid>
                </MainContentTemplate>
            </AuthenticatedLayout>
            <CreateGroup open={open} handleClose={handleClose} />
        </Fragment>
    );
};

export default Index;
