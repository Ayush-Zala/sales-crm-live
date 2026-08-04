import { Head, usePage } from "@inertiajs/react";
import { format, startOfMonth } from "date-fns";
import { useState } from "react";

import Grid from "@mui/material/Grid";
import Typography from "@mui/material/Typography";

import SimpleDataTable from "@/Components/SimpleDataTable";
import PaginatedTable from "@/Components/SimplePaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { formatDateTime } from "@/utils/date-time-formatters";

import DateRangeFilter from "./DateRangeFilter";
import FilterComponent from "./FilterComponent";
import IndustryFilterComponent from "./IndustryFilterComponent";
import UserListFilterComponent from "./UserListFilterComponent";
import UserReportFilterComponent from "./UserReportFilterComponent";
import { ReportDataSearch } from "./report-data-search";
import { hasRole } from "@/utils/AccessManager";
import Report1Actions from "./cell-components/Report1Actions";

export default function Report({ auth }) {
    const isBDMOrSales = hasRole(auth, [
        "Business Development Manager",
        "Business Development Team Lead",
        "Sales Executives",
    ]);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Report" />
            <MainContentTemplate title="Report" subtitle="View report here">
                {!isBDMOrSales && <Report1 />}
                {!isBDMOrSales && <Report2 />}
                <Report3 />
                {!isBDMOrSales && <Report4 />}
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}

const Report1 = () => {
    const {
        url,
        props: { report, users, industries },
    } = usePage();

    const params = extractUrlParams(url);

    const [state, setState] = useState([
        {
            startDate: startOfMonth(new Date()),
            endDate: new Date(),
            key: "selection",
        },
    ]);

    const handleChangeDateRange = (ranges) => {
        setState(ranges);

        const fromDate = encodeURIComponent(
            format(new Date(ranges[0]?.startDate), "yyyy-MM-dd")
        );

        const toDate = encodeURIComponent(
            format(new Date(ranges[0]?.endDate), "yyyy-MM-dd")
        );

        useUpdateSearchParam(
            fromDate && toDate
                ? {
                      companyFromDate: fromDate,
                      companyToDate: toDate,
                      page: 1,
                      per_page: 50,
                  }
                : { page: 1, per_page: 50 },
            "/report"
        );
    };

    const columns = [
        { id: "name", label: "Company", textAlign: "left" },
        { id: "industry", label: "Industry", textAlign: "left" },
        { id: "assign_to", label: "Assign To", textAlign: "left" },
        { id: "assign_by", label: "Assign By", textAlign: "left" },
        { id: "created_at", label: "Created At", textAlign: "left" },
        { id: "action", label: "Action", align: "right" },
    ];

    return (
        <Grid container item columns={12} spacing={2}>
            <Grid item xs={12}>
                <Typography variant="h6">Assign Companies Report </Typography>
            </Grid>
            <Grid item xs={12}>
                <Grid
                    container
                    item
                    columns={12}
                    spacing={2}
                    alignItems="center"
                    justifyContent="flex-end"
                >
                    <Grid item lg={3} md={6} xs={12}>
                        <DateRangeFilter
                            state={state}
                            setState={handleChangeDateRange}
                        />
                    </Grid>
                    <Grid item>
                        <FilterComponent search={params.companyAssignStatus} />
                    </Grid>
                    <Grid item lg={3} md={6} xs={12}>
                        <IndustryFilterComponent
                            industries={industries}
                            search={params.industry}
                        />
                    </Grid>
                    <Grid item lg={3} md={6} xs={12}>
                        <UserListFilterComponent
                            users={users}
                            search={params.user}
                            param={"user"}
                        />
                    </Grid>
                    <Grid item xs={12}>
                        <ReportDataSearch search={params.search} />
                    </Grid>
                </Grid>
            </Grid>
            <Grid item xs={12}>
                <PaginatedTable
                    columns={columns}
                    rows={report.data}
                    CellComponent={Report1CellComponent}
                    current_page={report.current_page}
                    total={report.total}
                    per_page={report.per_page}
                    url={"/report"}
                />
            </Grid>
        </Grid>
    );
};

const Report1CellComponent = ({ row, column }) => {
    switch (column.id) {
        case "name":
            return <div>{row.company.name}</div>;
        case "industry":
            return <div>{row.company.industry}</div>;
        case "created_at":
            return <div>{formatDateTime(row.created_at)}</div>;
        case "assign_to":
            return (
                <div>{row.assign_to ? row.assign_to.name : "Not Assigned"}</div>
            );
        case "assign_by":
            return (
                <div>{row.assign_by ? row.assign_by.name : "Not Assigned"}</div>
            );
        case "action":
            return <Report1Actions companyId={row.company_id} />;
        default:
            return null;
    }
};

const Report2 = () => {
    const {
        url,
        props: {
            userReport: { usersReport, totalAssignedCompanies },
        },
    } = usePage();

    const params = extractUrlParams(url);

    const [state, setState] = useState([
        {
            startDate: startOfMonth(new Date()),
            endDate: new Date(),
            key: "selection",
        },
    ]);

    const handleChangeDateRange = (ranges) => {
        setState(ranges);

        const fromDate = encodeURIComponent(
            format(new Date(ranges[0]?.startDate), "yyyy-MM-dd")
        );

        const toDate = encodeURIComponent(
            format(new Date(ranges[0]?.endDate), "yyyy-MM-dd")
        );

        useUpdateSearchParam(
            fromDate && toDate
                ? { userFromDate: fromDate, userToDate: toDate }
                : {},
            "/report"
        );
    };

    const columns = [
        { id: "name", label: "Company", textAlign: "left" },
        {
            id: "reportingAuthority",
            label: "Reporting Authority",
            textAlign: "left",
        },
        {
            id: "assignCompaniesCount",
            label: `Assigned Companies (Total - ${totalAssignedCompanies})`,
            textAlign: "left",
        },
    ];

    return (
        <Grid container item columns={12} spacing={2}>
            <Grid item xs={12} lg={4}>
                <Typography variant="h6">User Report</Typography>
            </Grid>
            <Grid item xs={12} md={6} lg={5}>
                <UserReportFilterComponent search={params.userDataStatus} />
            </Grid>
            <Grid item xs={12} md={6} lg={3}>
                <DateRangeFilter
                    state={state}
                    setState={handleChangeDateRange}
                />
            </Grid>
            <Grid item xs={12}>
                <SimpleDataTable
                    columns={columns}
                    rows={usersReport}
                    CellComponent={Report2CellComponent}
                    tableMaxHeight={400}
                />
            </Grid>
        </Grid>
    );
};

const Report2CellComponent = ({ row, column }) => {
    switch (column.id) {
        case "name":
            return <div>{row.name}</div>;
        case "reportingAuthority":
            return <div>{row.reporting_authority.name}</div>;
        case "assignCompaniesCount":
            return <div>{row.assigned_companies_count}</div>;
        default:
            return null;
    }
};

const Report3 = () => {
    const {
        url,
        props: {
            auth,
            users,
            dispositionReport: { dispositions, totalDispositions },
        },
    } = usePage();

    const params = extractUrlParams(url);

    const [state, setState] = useState([
        {
            startDate: new Date(),
            endDate: new Date(),
            key: "selection",
        },
    ]);

    const handleChangeDateRange = (ranges) => {
        setState(ranges);

        const fromDate = encodeURIComponent(
            format(new Date(ranges[0]?.startDate), "yyyy-MM-dd")
        );

        const toDate = encodeURIComponent(
            format(new Date(ranges[0]?.endDate), "yyyy-MM-dd")
        );

        useUpdateSearchParam(
            fromDate && toDate
                ? {
                      dispositionFromDate: fromDate,
                      dispositionToDate: toDate,
                  }
                : {},
            "/report"
        );
    };

    const columns = [
        { id: "name", label: "Disposition", textAlign: "left" },
        {
            id: "dispositions_count",
            label: `Disposition Count (Total - ${totalDispositions})`,
            textAlign: "left",
        },
    ];

    const hasRoles = hasRole(auth, [
        "Admin",
        "Data Entry Manager",
        "Business Development Manager",
        "Business Development Team Lead",
    ]);

    return (
        <Grid
            container
            item
            columns={12}
            spacing={2}
            alignItems="center"
            xs={12}
            lg={6}
        >
            <Grid item xl={4} xs={12}>
                <Typography variant="h6">Disposition Report</Typography>
            </Grid>
            <Grid item xl={3} lg={6} xs={12}>
                {hasRoles && (
                    <UserListFilterComponent
                        users={users}
                        search={params.dispositionUserFilter}
                        param={"dispositionUserFilter"}
                    />
                )}
            </Grid>
            <Grid item xl={5} lg={6} xs={12}>
                <DateRangeFilter
                    state={state}
                    setState={handleChangeDateRange}
                />
            </Grid>
            <Grid item xs={12}>
                <SimpleDataTable
                    columns={columns}
                    rows={dispositions}
                    CellComponent={Report3CellComponent}
                    tableMaxHeight={400}
                />
            </Grid>
        </Grid>
    );
};

const Report3CellComponent = ({ row, column }) => {
    switch (column.id) {
        case "name":
            return <div>{row.name}</div>;
        case "dispositions_count":
            return <div>{row.dispositions_count}</div>;
        default:
            return null;
    }
};

const Report4 = () => {
    const {
        props: {
            industryReport: { industries, totalIndustries },
        },
    } = usePage();

    const columns = [
        { id: "industry", label: "Industry", textAlign: "left" },
        {
            id: "company_count",
            label: `Company Count (Total - ${totalIndustries})`,
            textAlign: "left",
        },
    ];

    return (
        <Grid
            container
            item
            columns={12}
            spacing={2}
            xs={12}
            lg={6}
            alignSelf="baseline"
        >
            <Grid item xl={4} xs={12}>
                <Typography variant="h6">Industry Report</Typography>
            </Grid>
            <Grid item xs={12}>
                <SimpleDataTable
                    columns={columns}
                    rows={industries}
                    CellComponent={Report4CellComponent}
                    tableMaxHeight={400}
                />
            </Grid>
        </Grid>
    );
};

const Report4CellComponent = ({ row, column }) => {
    switch (column.id) {
        case "industry":
            return <div>{row.industry}</div>;
        case "company_count":
            return <div>{row.company_count}</div>;
        default:
            return null;
    }
};
