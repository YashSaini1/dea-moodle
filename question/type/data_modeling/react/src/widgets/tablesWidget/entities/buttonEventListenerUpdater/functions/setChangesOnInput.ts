import { TARGET_INPUT_ELEMENT_ID } from '@constants/constants';
import { Parser } from '@dbml/core';
import {
  IInitialStateFromInput,
  ISetChangesOnInputParams,
} from '@projectTypes/interfaces';
import { trim } from 'lodash';

export const setChangesOnInput = (params: ISetChangesOnInputParams) => {
  const { nodes, edges, editorText } = params;

  try {
    const trimmedEditorText = trim(editorText);
    const parserResult = Parser.parse(trimmedEditorText, 'dbml');

    if (parserResult) {
      const targetInput = document.getElementById(
        TARGET_INPUT_ELEMENT_ID,
      ) as HTMLInputElement;

      /*const nodesClone = cloneDeep(nodes);
      const edgesClone = cloneDeep(edges);

      const nodesForServer = nodesClone.map((node) => {
        const { data, type, ...preNodeForServer } = node;

        const { tableWidth, tableHeight, ...dataFieldForServer } = node.data;

        const nodeForServer: NodeForServerType = {
          ...preNodeForServer,
          data: {
            ...dataFieldForServer,
          },
        };

        return nodeForServer;
      });

      const edgesForServer = edgesClone.map((edge) => {
        const {
          animated,
          style,
          sourceHandle,
          targetHandle,
          type,
          data,
          ...preEdgeForServer
        } = edge;

        const { pathOptions, ...dataFieldForServer } = edge.data;

        const edgeForServer: EdgeForServerType = {
          ...preEdgeForServer,
          data: {
            ...dataFieldForServer,
          },
        };

        return edgeForServer;
      });

      const dataForServerAsObject: IDataForServer = {
        refs: edgesForServer,
        tables: nodesForServer,
      };*/

      const dataForServerAsObject: IInitialStateFromInput = {
        refs: edges,
        tables: nodes,
        editorText: trimmedEditorText,
      };

      const dataForServerAsString = JSON.stringify(dataForServerAsObject);

      if (targetInput) {
        targetInput.value = dataForServerAsString;
      }
    }
  } catch (error) {
    return;
  }
};
