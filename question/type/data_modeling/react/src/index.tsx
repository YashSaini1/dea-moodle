import React from 'react';

import { ROOT_CONTAINER_ID } from '@constants/constants';
import store from '@store/redux/store';
import ReactDOM from 'react-dom/client';
import { Provider } from 'react-redux';

import { App } from './App';

const root = ReactDOM.createRoot(
  document.getElementById(ROOT_CONTAINER_ID) as HTMLElement,
);

root.render(
  <React.StrictMode>
    <Provider store={store}>
      <App />
    </Provider>
  </React.StrictMode>,
);
