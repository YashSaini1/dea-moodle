import React, { useEffect, useLayoutEffect, useReducer } from 'react';

import 'reactflow/dist/style.css';
import { SvgDefinitions } from '@components/ui/svgDefinitions/SvgDefinitions';
import {
  CONNECTION_LINE_ID,
  CONNECTION_RADIUS,
  DEFAULT_ZOOM,
  DIV_TAG_NAME,
  FIT_VIEW_MAX_ZOOM,
  FIT_VIEW_PADDING,
  MAX_ZOOM,
  MIN_ZOOM,
  SPAN_TAG_NAME,
  TABLES_SECOND_RENDER_TIMEOUT,
  TARGET_INPUT_ELEMENT_ID,
} from '@constants/constants';
import { Parser } from '@dbml/core';
import {
  IInitialStateFromInput,
  INewEditorTextEventData,
  ITableEdgeData,
  ITableNodeData,
  ITablesWidgetProps,
} from '@projectTypes/interfaces';
import { DragEventType } from '@projectTypes/types';
import {
  edgeTypes,
  nodeTypes,
  reducerAction,
  reducerInitialState,
} from '@store/initialValues';
import {
  getConnectionSourceTempNode,
  getEdges,
  getEditorText,
  getErrorMessage,
  getIsConnectionComplete,
  getIsNeedToRemoveTempLine,
  getNewEdge,
  getNodes,
} from '@store/redux/stage/stageSelectors';
import {
  setConnectionSourceTempNode,
  setEditorText,
  setIsConnectionComplete,
  setIsCursorLeave,
  setIsNeedToRemoveTempLine,
  setIsTableDragging,
  setNewEdge,
  setSourceTableId,
} from '@store/redux/stage/stageSlice';
import { useAppDispatch } from '@store/redux/store';
import { cloneDeep, trim } from 'lodash';
import { nanoid } from 'nanoid';
import { useSelector } from 'react-redux';
import {
  addEdge,
  ReactFlow,
  useEdgesState,
  useNodesState,
  useReactFlow,
  useUpdateNodeInternals,
} from 'reactflow';

import { ButtonEventListenerUpdater } from './entities/buttonEventListenerUpdater/ButtonEventListenerUpdater';
import { ControlPanel } from './entities/controlPanel/ControlPanel';
import { ErrorContainer } from './entities/errorContainer/ErrorContainer';
import { analyzeEditorText } from './functions/analyzeEditorText';
import { catchEditorTextError } from './functions/catchEditorTextError';
import { edgesParser } from './functions/edgesParser';
import { tablesParser } from './functions/tablesParser';

export const TablesWidget = (props: ITablesWidgetProps) => {
  const { containerClassName, sendButtonIds, editorTextareaElement } = props;

  const targetInputElement = document.getElementById(
    TARGET_INPUT_ELEMENT_ID,
  ) as HTMLInputElement;
  const sendButton1 = document.getElementById(sendButtonIds[0]) as HTMLInputElement;
  const sendButton2 = document.getElementById(sendButtonIds[1]) as HTMLInputElement;

  const dispatch = useAppDispatch();
  const updateNodeInternals = useUpdateNodeInternals();
  const { fitView } = useReactFlow();

  const editorText = useSelector(getEditorText);
  const errorMessage = useSelector(getErrorMessage);
  const nodesFromStore = useSelector(getNodes);
  const edgesFromStore = useSelector(getEdges);
  const isNeedToRemoveTempLine = useSelector(getIsNeedToRemoveTempLine);
  const isConnectionComplete = useSelector(getIsConnectionComplete);
  const connectionSourceTempNode = useSelector(getConnectionSourceTempNode);
  const newEdge = useSelector(getNewEdge);

  const [nodes, setNodes, onNodesChange] = useNodesState<ITableNodeData>(nodesFromStore);
  const [edges, setEdges, onEdgeChange] = useEdgesState<ITableEdgeData>(edgesFromStore);
  const [reducerState, reducerDispatch] = useReducer(reducerAction, reducerInitialState);

  const {
    shouldRerenderForSaveButton,
    isEnabledTablesCommonRender,
    shouldTablesSecondRender,
    shouldTablesFromTargetInputRender,
    isShouldFitView,
    updateButtonEventListener,
  } = reducerState;

  // set dragging mode for prevent sizes recalculation in useTableFieldSize for all tables
  const onNodeDragStart = () => {
    dispatch(setIsTableDragging(true));
  };

  const onNodeDragStop = (event: DragEventType) => {
    const nodesClone = cloneDeep(nodes);
    const target = event.target as HTMLDivElement | HTMLSpanElement;
    const targetTagName = target.tagName;
    const tableIds = nodes.map((node) => {
      return node.id;
    });

    updateNodeInternals(tableIds);

    reducerDispatch({ type: 'setShouldRerenderForSaveButton', payload: '' });

    dispatch(setIsTableDragging(false));

    if (targetTagName === DIV_TAG_NAME) {
      const tableDivChild = target.children[0] as HTMLSpanElement;
      const tableName = tableDivChild.innerHTML;
      const targetTableIndex = nodesClone.findIndex((table) => {
        return table.data.tableName === tableName;
      });

      if (nodesClone[targetTableIndex] && !nodesClone[targetTableIndex].data.isTouched) {
        nodesClone[targetTableIndex].data.isTouched = true;

        setNodes(nodesClone);
      }
    } else if (targetTagName === SPAN_TAG_NAME) {
      const tableName = target.innerHTML;
      const targetTableIndex = nodesClone.findIndex((table) => {
        return table.data.tableName === tableName;
      });

      if (nodesClone[targetTableIndex] && !nodesClone[targetTableIndex].data.isTouched) {
        nodesClone[targetTableIndex].data.isTouched = true;

        setNodes(nodesClone);
      }
    }
  };

  // update nodes. This allows you to change the position of edges(in the center or top
  // and bottom) in all tables during table moving process
  const onNodeDrag = () => {
    const tableIds = nodes.map((node) => {
      return node.id;
    });

    updateNodeInternals(tableIds);
  };

  const onMouseUp = () => {
    if (connectionSourceTempNode) {
      dispatch(setIsConnectionComplete(true));
      dispatch(setIsCursorLeave(false));
    }

    dispatch(setSourceTableId(''));
  };

  // initialization event listener for editor input event
  useEffect(() => {
    if (sendButton1) {
      sendButton1.removeAttribute('disabled');
    }

    if (sendButton2) {
      sendButton2.removeAttribute('disabled');
    }

    document.addEventListener('editor_input', (event) => {
      analyzeEditorText({ sendButtonIds, event, dispatch, reducerDispatch });
    });

    return () => {
      document.removeEventListener('editor_input', (event) => {
        analyzeEditorText({ sendButtonIds, event, dispatch, reducerDispatch });
      });
    };
  }, []);

  // *** special effects start *** //

  // initial
  useEffect(() => {
    const targetInputValue = targetInputElement.value;

    if (targetInputValue) {
      const objectFromTargetInput: IInitialStateFromInput = JSON.parse(targetInputValue);
      const trimmedEditorText = trim(objectFromTargetInput.editorText);
      const editorTextEvent = new CustomEvent<INewEditorTextEventData>(
        'editor_set_new_value',
        { bubbles: true, cancelable: true, detail: { text: trimmedEditorText } },
      );

      document.dispatchEvent(editorTextEvent);

      reducerDispatch({ type: 'setShouldTablesFromTargetInputRender', payload: true });
    } else {
      try {
        const editorText = editorTextareaElement.value;
        const trimmedEditorText = trim(editorText);
        const parserResult = Parser.parse(trimmedEditorText, 'dbml');

        if (parserResult) {
          dispatch(setEditorText(trimmedEditorText));

          reducerDispatch({
            type: 'setShouldTablesFromTargetInputRender',
            payload: false,
          });
          reducerDispatch({ type: 'setIsShouldFitView', payload: true });
          reducerDispatch({ type: 'setIsEnabledTablesCommonRender', payload: true });
        }
      } catch (error) {
        catchEditorTextError({
          sendButtonIds,
          correctEditorText: editorText,
          error,
          dispatch,
        });
      }
    }
  }, []);

  // set data
  useEffect(() => {
    if (shouldTablesFromTargetInputRender) {
      const targetInputValue = targetInputElement.value;
      const objectFromTargetInput: IInitialStateFromInput = JSON.parse(targetInputValue);
      const trimmedEditorText = trim(objectFromTargetInput.editorText);
      const parserResult = Parser.parse(trimmedEditorText, 'dbml');

      dispatch(setEditorText(trimmedEditorText));

      setNodes(
        tablesParser({
          editorText: trimmedEditorText,
          parserResult,
          nodes: objectFromTargetInput.tables,
        }),
      );

      reducerDispatch({ type: 'setShouldTablesFromTargetInputRender', payload: false });
      reducerDispatch({ type: 'setIsShouldFitView', payload: true });
      reducerDispatch({ type: 'setIsEnabledTablesCommonRender', payload: true });
    }
  }, [shouldTablesFromTargetInputRender]);

  // *** special effects end *** //

  // *** common effects start *** //

  // common rerender 1
  useEffect(() => {
    const parserResult = Parser.parse(editorText, 'dbml');
    const parserResultForFirstRender = tablesParser({ editorText, parserResult, nodes });

    if (isEnabledTablesCommonRender) {
      setNodes(parserResultForFirstRender);

      if (!shouldTablesSecondRender) {
        reducerDispatch({ type: 'setShouldTablesSecondRender', payload: true });
      }
    }
  }, [editorText, isEnabledTablesCommonRender]);

  // common rerender 2
  useEffect(() => {
    if (shouldTablesSecondRender) {
      setTimeout(() => {
        const parserResult = Parser.parse(editorText, 'dbml');
        const tableIds = nodes.map((node) => {
          return node.id;
        });

        const parserResultForSecondRender = tablesParser({
          editorText,
          parserResult,
          nodes,
        });

        setNodes(parserResultForSecondRender);

        updateNodeInternals(tableIds);

        reducerDispatch({ type: 'setShouldTablesSecondRender', payload: false });
        reducerDispatch({ type: 'setIsShouldFitView', payload: true });
      }, TABLES_SECOND_RENDER_TIMEOUT);
    }
  }, [nodes, shouldTablesSecondRender]);

  // edges rendering. Doesn't affect tables
  useEffect(() => {
    const parserResult = Parser.parse(editorText, 'dbml');

    if (nodes.length > 0 && parserResult) {
      setEdges(edgesParser({ editorText, parserResult, nodes, edges }));
    }
  }, [nodes, setEdges]);

  // *** common effects end *** //

  useEffect(() => {
    if (isShouldFitView) {
      fitView({
        maxZoom: FIT_VIEW_MAX_ZOOM,
        minZoom: MIN_ZOOM,
        padding: FIT_VIEW_PADDING,
        includeHiddenNodes: true,
      });

      reducerDispatch({ type: 'setIsShouldFitView', payload: false });
    }
  }, [isShouldFitView]);

  // when old button event listener component with irrelevant data was unmounted,
  // create new button event listener component with relevant data
  useLayoutEffect(() => {
    if (updateButtonEventListener === '') {
      reducerDispatch({ type: 'setUpdateButtonEventListener', payload: nanoid() });
    }
  }, [updateButtonEventListener]);

  useLayoutEffect(() => {
    if (!shouldRerenderForSaveButton) {
      setNodes(nodes);

      reducerDispatch({ type: 'setShouldRerenderForSaveButton', payload: nanoid() });
    }
  }, [shouldRerenderForSaveButton]);

  // unmount button event listener component for delete its event listener with irrelevant data
  useLayoutEffect(() => {
    reducerDispatch({ type: 'setUpdateButtonEventListener', payload: '' });
  }, [nodes, edges]);

  // add a temporary edge with onMouseDown with target to new field
  useEffect(() => {
    if (newEdge) {
      setEdges((edges) => {
        const isConnectionLineAlreadyExist =
          edges.findIndex((edge) => edge.id === CONNECTION_LINE_ID) !== -1;

        if (isConnectionLineAlreadyExist) {
          return edges.map((edge) => {
            if (edge.id === CONNECTION_LINE_ID) {
              return newEdge;
            }

            return edge;
          });
        }

        return addEdge(
          newEdge,
          edges.map((edge) => ({ ...edge })),
        );
      });

      dispatch(setNewEdge(null));
    }
  }, [newEdge]);

  // removing animation on temporary edge and assigning id !== CONNECTION_LINE_ID, the temporary edge becomes permanent
  useEffect(() => {
    if (isConnectionComplete) {
      setEdges((edges) => {
        return edges.map((edge) => {
          if (edge.id === CONNECTION_LINE_ID) {
            return {
              ...edge,
              id: `${new Date().getTime()}`,
              animated: false,
            };
          }

          return edge;
        });
      });

      dispatch(setIsConnectionComplete(false));
      dispatch(setConnectionSourceTempNode(null));
    }
  }, [isConnectionComplete]);

  // remove the temporary edge every time when user leaves field in the table.
  // At the same time, fields join functionality is still active
  useEffect(() => {
    if (isNeedToRemoveTempLine) {
      setEdges((edges) => edges.filter((edge) => edge.id !== CONNECTION_LINE_ID));
      dispatch(setIsNeedToRemoveTempLine(false));
    }
  }, [isNeedToRemoveTempLine]);

  return (
    <div className={containerClassName ? containerClassName : 'TableWidgetContainer'}>
      <ErrorContainer />
      <SvgDefinitions />
      {updateButtonEventListener && !errorMessage && (
        <ButtonEventListenerUpdater
          buttonIds={sendButtonIds}
          edges={edges}
          editorText={editorText}
          nodes={nodes}
        />
      )}
      <ReactFlow
        connectionRadius={CONNECTION_RADIUS}
        defaultViewport={{ x: 0, y: 0, zoom: DEFAULT_ZOOM }}
        deleteKeyCode={null}
        edges={!shouldRerenderForSaveButton ? [] : edges}
        edgeTypes={edgeTypes}
        maxZoom={MAX_ZOOM}
        minZoom={MIN_ZOOM}
        nodes={!shouldRerenderForSaveButton ? [] : nodes}
        nodeTypes={nodeTypes}
        onEdgesChange={onEdgeChange}
        onNodeDrag={onNodeDrag}
        onNodeDragStart={onNodeDragStart}
        onNodeDragStop={onNodeDragStop}
        onNodesChange={onNodesChange}
        zoomOnDoubleClick={false}
        /*onMouseUp={onMouseUp}*/
      >
        <ControlPanel />
      </ReactFlow>
    </div>
  );
};
