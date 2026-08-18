import BrandLogo from './brand-logo';

export default function AppLogo() {
    return (
        <>
            <BrandLogo variant="sidebar" className="group-data-[collapsible=icon]:hidden" />
            <BrandLogo variant="sidebarIcon" useFavicon className="hidden group-data-[collapsible=icon]:block" />
        </>
    );
}
