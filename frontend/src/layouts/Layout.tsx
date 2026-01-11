import React from "react";

const Layout = ({ children }: { children: React.ReactNode }) => {
  return (
    <div
      className={`min-w-screen min-h-screen dark:bg-gray-800 bg-gray-200 dark:text-gray-200 text-gray-850 transition-colors duration-300`}
    >
      {children}
    </div>
  );
};

export default Layout;
