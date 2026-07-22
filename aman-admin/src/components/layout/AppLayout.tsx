import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Topbar } from './Topbar';
import { useState, useEffect, createContext, useContext } from 'react';

const PageTitleContext = createContext<(title: string) => void>(() => {});
export const usePageTitle = (title: string) => {
  const setTitle = useContext(PageTitleContext);
  useEffect(() => {
    setTitle(title);
  }, [title, setTitle]);
};

export function AppLayout() {
  const [title, setTitle] = useState('لوحة المعلومات');

  return (
    <PageTitleContext.Provider value={setTitle}>
      <div className="flex h-screen overflow-hidden bg-sand">
        <Sidebar />
        <div className="flex flex-1 flex-col overflow-hidden">
          <Topbar title={title} />
          <main className="flex-1 overflow-y-auto p-6">
            <Outlet />
          </main>
        </div>
      </div>
    </PageTitleContext.Provider>
  );
}
