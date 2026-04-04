import React from 'react';

import { ADMIN_PAGE_ATTRIBUTE, ROOT_CONTAINER_ID } from '@constants/constants';
import { AdminPage } from '@pages/AdminPage/AdminPage';
import { SqlPage } from '@pages/SqlPage/SqlPage';
import '@scss/react/global.scss';
import '@scss/react/variables.scss';

export const App = () => {
  const rootDivElement = document.getElementById(ROOT_CONTAINER_ID) as HTMLDivElement;
  const rootContainerAttribute = rootDivElement.getAttribute('page');

  const isShowAdminPageCondition = rootContainerAttribute === ADMIN_PAGE_ATTRIBUTE;

  if (isShowAdminPageCondition) {
    rootDivElement.style.paddingBottom = '15px';

    return <AdminPage />;
  } else {
    rootDivElement.style.paddingBottom = '0';

    return <SqlPage />;
  }
};
